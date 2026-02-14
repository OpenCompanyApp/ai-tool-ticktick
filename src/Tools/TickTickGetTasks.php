<?php

namespace OpenCompany\AiToolTickTick\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolTickTick\TickTickService;

class TickTickGetTasks implements Tool
{
    public function __construct(
        private TickTickService $service,
    ) {}

    public function description(): string
    {
        return 'Get all tasks in a TickTick project. Returns task titles, IDs, priorities, due dates, and subtasks.';
    }

    public function handle(Request $request): string
    {
        try {
            if (! $this->service->isConfigured()) {
                return 'Error: TickTick integration is not configured.';
            }

            $projectData = $this->service->getProjectWithData($request['projectId']);
            $tasks = $projectData['tasks'] ?? [];

            if (empty($tasks)) {
                return 'No tasks found in this project.';
            }

            return json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return "Error getting TickTick tasks: {$e->getMessage()}";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'projectId' => $schema
                ->string()
                ->description('The project ID to get tasks from. Use ticktick_list_projects to find project IDs.')
                ->required(),
        ];
    }
}
