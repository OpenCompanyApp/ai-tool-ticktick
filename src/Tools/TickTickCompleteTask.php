<?php

namespace OpenCompany\AiToolTickTick\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolTickTick\TickTickService;

class TickTickCompleteTask implements Tool
{
    public function __construct(
        private TickTickService $service,
    ) {}

    public function description(): string
    {
        return 'Mark a TickTick task as complete.';
    }

    public function handle(Request $request): string
    {
        try {
            if (! $this->service->isConfigured()) {
                return 'Error: TickTick integration is not configured.';
            }

            $this->service->completeTask($request['projectId'], $request['taskId']);

            return "Task '{$request['taskId']}' marked as complete.";
        } catch (\Throwable $e) {
            return "Error completing TickTick task: {$e->getMessage()}";
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
                ->description('The task ID to complete.')
                ->required(),
        ];
    }
}
