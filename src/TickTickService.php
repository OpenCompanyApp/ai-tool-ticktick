<?php

namespace OpenCompany\AiToolTickTick;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TickTickService
{
    public function __construct(
        private string $accessToken = '',
        private string $baseUrl = 'https://api.ticktick.com',
    ) {
        $this->baseUrl = rtrim($this->baseUrl, '/');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->accessToken);
    }

    // ── Projects ────────────────────────────────────────────

    /**
     * List all projects.
     */
    public function listProjects(): array
    {
        return $this->request('GET', '/open/v1/project');
    }

    /**
     * Get a project by ID.
     */
    public function getProject(string $projectId): array
    {
        return $this->request('GET', '/open/v1/project/' . urlencode($projectId));
    }

    /**
     * Get a project with its tasks and columns.
     */
    public function getProjectWithData(string $projectId): array
    {
        return $this->request('GET', '/open/v1/project/' . urlencode($projectId) . '/data');
    }

    /**
     * Create a new project.
     */
    public function createProject(array $data): array
    {
        return $this->request('POST', '/open/v1/project', $data);
    }

    /**
     * Update a project.
     */
    public function updateProject(string $projectId, array $data): array
    {
        return $this->request('POST', '/open/v1/project/' . urlencode($projectId), $data);
    }

    /**
     * Delete a project.
     */
    public function deleteProject(string $projectId): void
    {
        $this->request('DELETE', '/open/v1/project/' . urlencode($projectId));
    }

    // ── Tasks ───────────────────────────────────────────────

    /**
     * Get a task by project ID and task ID.
     */
    public function getTask(string $projectId, string $taskId): array
    {
        return $this->request('GET', '/open/v1/project/' . urlencode($projectId) . '/task/' . urlencode($taskId));
    }

    /**
     * Create a new task.
     */
    public function createTask(array $data): array
    {
        return $this->request('POST', '/open/v1/task', $data);
    }

    /**
     * Update a task.
     */
    public function updateTask(string $taskId, array $data): array
    {
        return $this->request('POST', '/open/v1/task/' . urlencode($taskId), $data);
    }

    /**
     * Complete a task.
     */
    public function completeTask(string $projectId, string $taskId): void
    {
        $this->request('POST', '/open/v1/project/' . urlencode($projectId) . '/task/' . urlencode($taskId) . '/complete');
    }

    /**
     * Delete a task.
     */
    public function deleteTask(string $projectId, string $taskId): void
    {
        $this->request('DELETE', '/open/v1/project/' . urlencode($projectId) . '/task/' . urlencode($taskId));
    }

    // ── HTTP ────────────────────────────────────────────────

    /**
     * Make an API request and return parsed JSON.
     */
    private function request(string $method, string $path, array $data = []): array
    {
        $response = $this->rawRequest($method, $path, $data);

        return $response->json() ?? [];
    }

    /**
     * Make a raw HTTP request to the TickTick API.
     */
    private function rawRequest(string $method, string $path, array $data = []): \Illuminate\Http\Client\Response
    {
        if (! $this->accessToken) {
            throw new \RuntimeException('TickTick access token is not configured.');
        }

        $url = $this->baseUrl . $path;

        try {
            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(30);

            $response = match (strtoupper($method)) {
                'GET' => $http->get($url, $data),
                'POST' => $http->post($url, $data),
                'PUT' => $http->put($url, $data),
                'DELETE' => $http->delete($url, $data),
                default => throw new \RuntimeException("Unsupported HTTP method: {$method}"),
            };

            if (! $response->successful()) {
                $contentType = $response->header('Content-Type');
                $body = $response->body();

                if (str_contains($contentType ?? '', 'text/html') || str_starts_with(trim($body), '<!DOCTYPE')) {
                    Log::warning("TickTick API returned HTML for {$method} {$path}", [
                        'status' => $response->status(),
                    ]);
                    throw new \RuntimeException("TickTick API endpoint not available (HTTP {$response->status()}). Check your access token and base URL.");
                }

                $error = $response->json('error_description') ?? $response->json('error') ?? $body;
                Log::error("TickTick API error: {$method} {$path}", [
                    'status' => $response->status(),
                    'error' => $error,
                ]);
                throw new \RuntimeException('TickTick API error (' . $response->status() . '): ' . (is_string($error) ? $error : json_encode($error)));
            }

            return $response;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("TickTick API connection error: {$method} {$path}", [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException("Failed to connect to TickTick API: {$e->getMessage()}");
        }
    }
}
