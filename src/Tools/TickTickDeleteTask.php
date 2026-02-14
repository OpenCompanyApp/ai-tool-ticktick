<?php

namespace OpenCompany\AiToolTickTick\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolTickTick\TickTickService;

class TickTickDeleteTask implements Tool
{
    public function __construct(
        private TickTickService $service,
    ) {}

    public function description(): string
    {
        return 'Delete a TickTick task. This action cannot be undone.';
    }

    public function handle(Request $request): string
    {
        try {
            if (! $this->service->isConfigured()) {
                return 'Error: TickTick integration is not configured.';
            }

            $this->service->deleteTask($request['projectId'], $request['taskId']);

            return "Task '{$request['taskId']}' deleted successfully.";
        } catch (\Throwable $e) {
            return "Error deleting TickTick task: {$e->getMessage()}";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'projectId' => $schema
                ->string()
                ->description('The project ID the task belongs to.')
                ->required(),
            'taskId' => $schema
                ->string()
                ->description('The task ID to delete.')
                ->required(),
        ];
    }
}
