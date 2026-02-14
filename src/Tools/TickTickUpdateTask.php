<?php

namespace OpenCompany\AiToolTickTick\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolTickTick\TickTickService;

class TickTickUpdateTask implements Tool
{
    public function __construct(
        private TickTickService $service,
    ) {}

    public function description(): string
    {
        return 'Update an existing TickTick task. Requires both the task ID and its project ID. Only provided fields will be changed.';
    }

    public function handle(Request $request): string
    {
        try {
            if (! $this->service->isConfigured()) {
                return 'Error: TickTick integration is not configured.';
            }

            $data = [
                'id' => $request['taskId'],
                'projectId' => $request['projectId'],
            ];

            if (isset($request['title'])) {
                $data['title'] = $request['title'];
            }
            if (isset($request['content'])) {
                $data['content'] = $request['content'];
            }
            if (isset($request['startDate'])) {
                $data['startDate'] = $request['startDate'];
            }
            if (isset($request['dueDate'])) {
                $data['dueDate'] = $request['dueDate'];
            }
            if (isset($request['priority'])) {
                $data['priority'] = (int) $request['priority'];
            }

            $result = $this->service->updateTask($request['taskId'], $data);

            return "Task updated successfully.\n" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return "Error updating TickTick task: {$e->getMessage()}";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'taskId' => $schema
                ->string()
                ->description('The task ID to update.')
                ->required(),
            'projectId' => $schema
                ->string()
                ->description('The project ID the task belongs to.')
                ->required(),
            'title' => $schema
                ->string()
                ->description('New title for the task.'),
            'content' => $schema
                ->string()
                ->description('New description/notes for the task.'),
            'startDate' => $schema
                ->string()
                ->description('New start date in ISO 8601 format.'),
            'dueDate' => $schema
                ->string()
                ->description('New due date in ISO 8601 format.'),
            'priority' => $schema
                ->integer()
                ->description('New priority: 0 = none, 1 = low, 3 = medium, 5 = high.'),
        ];
    }
}
