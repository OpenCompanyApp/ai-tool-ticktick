<?php

namespace OpenCompany\AiToolTickTick;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenCompany\IntegrationCore\Contracts\CredentialResolver;
use OpenCompany\IntegrationCore\Support\ToolProviderRegistry;

class AiToolTickTickServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TickTickService::class, function ($app) {
            $creds = $app->make(CredentialResolver::class);

            return new TickTickService(
                accessToken: $creds->get('ticktick', 'access_token', ''),
                baseUrl: $creds->get('ticktick', 'base_url', 'https://api.ticktick.com'),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->bound(ToolProviderRegistry::class)) {
            $this->app->make(ToolProviderRegistry::class)
                ->register(new TickTickToolProvider());
        }

        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        Route::prefix('api/integrations/ticktick/oauth')
            ->middleware('web')
            ->group(function () {
                Route::get('authorize', [TickTickOAuthController::class, 'authorize']);
                Route::get('callback', [TickTickOAuthController::class, 'callback']);
            });
    }
}
