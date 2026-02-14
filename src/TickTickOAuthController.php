<?php

namespace OpenCompany\AiToolTickTick;

use App\Models\IntegrationSetting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TickTickOAuthController extends Controller
{
    /**
     * Redirect the user to TickTick's OAuth authorization page.
     */
    public function authorize(Request $request)
    {
        $setting = IntegrationSetting::where('integration_id', 'ticktick')->first();
        $clientId = $setting?->getConfigValue('client_id');

        if (! $clientId) {
            return redirect('/settings?tab=integrations')
                ->with('error', 'TickTick Client ID is not configured. Save your Client ID first.');
        }

        $baseUrl = $this->getOAuthBaseUrl($setting);
        $state = Str::random(40);
        $request->session()->put('ticktick_oauth_state', $state);

        $redirectUri = url('/api/integrations/ticktick/oauth/callback');

        $query = http_build_query([
            'client_id' => $clientId,
            'scope' => 'tasks:write tasks:read',
            'state' => $state,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
        ]);

        return redirect("{$baseUrl}/oauth/authorize?{$query}");
    }

    /**
     * Handle the OAuth callback from TickTick.
     */
    public function callback(Request $request)
    {
        $storedState = $request->session()->pull('ticktick_oauth_state');

        if (! $storedState || $storedState !== $request->input('state')) {
            return redirect('/settings?tab=integrations')
                ->with('error', 'Invalid OAuth state. Please try connecting again.');
        }

        $code = $request->input('code');
        if (! $code) {
            $error = $request->input('error_description', $request->input('error', 'No authorization code received.'));

            return redirect('/settings?tab=integrations')
                ->with('error', "TickTick authorization failed: {$error}");
        }

        $setting = IntegrationSetting::where('integration_id', 'ticktick')->first();
        if (! $setting) {
            return redirect('/settings?tab=integrations')
                ->with('error', 'TickTick integration not found. Save your Client ID and Secret first.');
        }

        $clientId = $setting->getConfigValue('client_id');
        $clientSecret = $setting->getConfigValue('client_secret');
        $baseUrl = $this->getOAuthBaseUrl($setting);
        $redirectUri = url('/api/integrations/ticktick/oauth/callback');

        try {
            $response = Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->timeout(15)
                ->post("{$baseUrl}/oauth/token", [
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'scope' => 'tasks:write tasks:read',
                    'redirect_uri' => $redirectUri,
                ]);

            if (! $response->successful()) {
                $error = $response->json('error_description') ?? $response->json('error') ?? $response->body();

                return redirect('/settings?tab=integrations')
                    ->with('error', 'Failed to exchange token: ' . (is_string($error) ? $error : json_encode($error)));
            }

            $data = $response->json();
            $accessToken = $data['access_token'] ?? null;

            if (! $accessToken) {
                return redirect('/settings?tab=integrations')
                    ->with('error', 'No access token in response.');
            }

            // Store the access token
            $config = $setting->config ?? [];
            $config['access_token'] = $accessToken;
            $setting->config = $config;
            $setting->enabled = true;
            $setting->save();

            return redirect('/settings?tab=integrations')
                ->with('success', 'TickTick connected successfully.');
        } catch (\Throwable $e) {
            return redirect('/settings?tab=integrations')
                ->with('error', 'OAuth token exchange failed: ' . $e->getMessage());
        }
    }

    /**
     * Determine the OAuth base URL from the API base URL config.
     */
    private function getOAuthBaseUrl(?IntegrationSetting $setting): string
    {
        $apiBaseUrl = $setting?->getConfigValue('base_url', 'https://api.ticktick.com') ?? 'https://api.ticktick.com';

        // Dida365 (Chinese variant)
        if (str_contains($apiBaseUrl, 'dida365')) {
            return 'https://dida365.com';
        }

        return 'https://ticktick.com';
    }
}
