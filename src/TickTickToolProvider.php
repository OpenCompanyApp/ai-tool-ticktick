<?php

namespace OpenCompany\AiToolTickTick;

use Illuminate\Support\Facades\Http;
use Laravel\Ai\Contracts\Tool;
use OpenCompany\AiToolTickTick\Tools\TickTickCompleteTask;
use OpenCompany\AiToolTickTick\Tools\TickTickCreateProject;
use OpenCompany\AiToolTickTick\Tools\TickTickCreateTask;
use OpenCompany\AiToolTickTick\Tools\TickTickDeleteProject;
use OpenCompany\AiToolTickTick\Tools\TickTickDeleteTask;
use OpenCompany\AiToolTickTick\Tools\TickTickGetProject;
use OpenCompany\AiToolTickTick\Tools\TickTickGetTasks;
use OpenCompany\AiToolTickTick\Tools\TickTickListProjects;
use OpenCompany\AiToolTickTick\Tools\TickTickUpdateTask;
use OpenCompany\IntegrationCore\Contracts\ConfigurableIntegration;
use OpenCompany\IntegrationCore\Contracts\ToolProvider;

class TickTickToolProvider implements ToolProvider, ConfigurableIntegration
{
    public function appName(): string
    {
        return 'ticktick';
    }

    public function appMeta(): array
    {
        return [
            'label' => 'projects, tasks, complete, organize',
            'description' => 'Task management',
            'icon' => 'ph:check-square',
            'logo' => 'simple-icons:ticktick',
        ];
    }

    public function integrationMeta(): array
    {
        return [
            'name' => 'TickTick',
            'description' => 'Task management and to-do list app',
            'icon' => 'ph:check-square',
            'logo' => 'simple-icons:ticktick',
            'category' => 'productivity',
            'badge' => 'verified',
            'docs_url' => 'https://developer.ticktick.com',
        ];
    }

    public function configSchema(): array
    {
        return [
            [
                'key' => 'client_id',
                'type' => 'text',
                'label' => 'Client ID',
                'placeholder' => 'Your TickTick app Client ID',
                'hint' => 'From your app at <a href="https://developer.ticktick.com/manage" target="_blank">developer.ticktick.com</a>.',
                'required' => true,
            ],
            [
                'key' => 'client_secret',
                'type' => 'secret',
                'label' => 'Client Secret',
                'placeholder' => 'Your TickTick app Client Secret',
                'required' => true,
            ],
            [
                'key' => 'base_url',
                'type' => 'url',
                'label' => 'API Base URL',
                'placeholder' => 'https://api.ticktick.com',
                'hint' => 'Use <code>https://api.ticktick.com</code> for TickTick or <code>https://api.dida365.com</code> for Dida365.',
                'default' => 'https://api.ticktick.com',
            ],
            [
                'key' => 'access_token',
                'type' => 'oauth_connect',
                'label' => 'TickTick Account',
                'authorize_url' => '/api/integrations/ticktick/oauth/authorize',
                'redirect_uri' => '/api/integrations/ticktick/oauth/callback',
            ],
        ];
    }

    public function testConnection(array $config): array
    {
        $accessToken = $config['access_token'] ?? '';
        $baseUrl = rtrim($config['base_url'] ?? 'https://api.ticktick.com', '/');

        if (empty($accessToken)) {
            return ['success' => false, 'error' => 'No access token provided. Paste a token or use the OAuth flow to connect.'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(10)->get($baseUrl . '/open/v1/project');

            if ($response->successful()) {
                $projects = $response->json();
                $count = is_array($projects) ? count($projects) : 0;

                return [
                    'success' => true,
                    'message' => "Connected to TickTick. Found {$count} project(s).",
                ];
            }

            $error = $response->json('error_description') ?? $response->json('error') ?? $response->body();

            return [
                'success' => false,
                'error' => 'TickTick API error (' . $response->status() . '): ' . (is_string($error) ? $error : json_encode($error)),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function validationRules(): array
    {
        return [
            'access_token' => 'nullable|string',
            'client_id' => 'nullable|string',
            'client_secret' => 'nullable|string',
            'base_url' => 'nullable|url',
        ];
    }

    public function tools(): array
    {
        return [
            'ticktick_list_projects' => [
                'class' => TickTickListProjects::class,
                'type' => 'read',
                'name' => 'List Projects',
                'description' => 'List all TickTick projects.',
                'icon' => 'ph:folders',
            ],
            'ticktick_get_project' => [
                'class' => TickTickGetProject::class,
                'type' => 'read',
                'name' => 'Get Project',
                'description' => 'Get a project with its tasks and sections.',
                'icon' => 'ph:folder-open',
            ],
            'ticktick_create_project' => [
                'class' => TickTickCreateProject::class,
                'type' => 'write',
                'name' => 'Create Project',
                'description' => 'Create a new project (list).',
                'icon' => 'ph:folder-plus',
            ],
            'ticktick_delete_project' => [
                'class' => TickTickDeleteProject::class,
                'type' => 'write',
                'name' => 'Delete Project',
                'description' => 'Delete a project.',
                'icon' => 'ph:trash',
            ],
            'ticktick_get_tasks' => [
                'class' => TickTickGetTasks::class,
                'type' => 'read',
                'name' => 'Get Tasks',
                'description' => 'Get all tasks in a project.',
                'icon' => 'ph:list-checks',
            ],
            'ticktick_create_task' => [
                'class' => TickTickCreateTask::class,
                'type' => 'write',
                'name' => 'Create Task',
                'description' => 'Create a new task.',
                'icon' => 'ph:plus-circle',
            ],
            'ticktick_update_task' => [
                'class' => TickTickUpdateTask::class,
                'type' => 'write',
                'name' => 'Update Task',
                'description' => 'Update an existing task.',
                'icon' => 'ph:pencil-simple',
            ],
            'ticktick_complete_task' => [
                'class' => TickTickCompleteTask::class,
                'type' => 'write',
                'name' => 'Complete Task',
                'description' => 'Mark a task as complete.',
                'icon' => 'ph:check-circle',
            ],
            'ticktick_delete_task' => [
                'class' => TickTickDeleteTask::class,
                'type' => 'write',
                'name' => 'Delete Task',
                'description' => 'Delete a task.',
                'icon' => 'ph:trash',
            ],
        ];
    }

    public function isIntegration(): bool
    {
        return true;
    }

    public function createTool(string $class, array $context = []): Tool
    {
        $service = app(TickTickService::class);

        return new $class($service);
    }
}
