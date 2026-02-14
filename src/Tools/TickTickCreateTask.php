<?php

namespace OpenCompany\AiToolTickTick\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolTickTick\TickTickService;

class TickTickCreateTask implements Tool
{
    public function __construct(
        private TickTickService $service,
    ) {}

    public function description(): string
    {
        return 'Create a new task in TickTick. If no projectId is given, the task goes to the Inbox. Supports subtasks via the items array.';
    }

    public function handle(Request $request): string
    {
        try {
            if (! $this->service->isConfigured()) {
                return 'Error: TickTick integration is not configured.';
            }

            $data = ['title' => $request['title']];

            if (isset($request['projectId'])) {
                $data['projectId'] = $request['projectId'];
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
            if (isset($request['isAllDay'])) {
                $data['isAllDay'] = (bool) $request['isAllDay'];
            }
            if (isset($request['items'])) {
                $items = $request['items'];
                $data['items'] = is_string($items) ? json_decode($items, true) : $items;
            }

            $result = $this->service->createTask($data);

            return "Task '{$request['title']}' created successfully.\n" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return "Error creating TickTick task: {$e->getMessage()}";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema
                ->string()
                ->description('Task title.')
                ->required(),
            'projectId' => $schema
                ->string()
                ->description('Project ID to create the task in. Omit to add to Inbox.'),
            'content' => $schema
                ->string()
                ->description('Task description/notes.'),
            'startDate' => $schema
                ->string()
                ->description('Start date in ISO 8601 format (e.g., "2026-02-15T09:00:00+0000").'),
            'dueDate' => $schema
                ->string()
                ->description('Due date in ISO 8601 format (e.g., "2026-02-15T17:00:00+0000").'),
            'priority' => $schema
                ->integer()
                ->description('Priority: 0 = none, 1 = low, 3 = medium, 5 = high.'),
            'isAllDay' => $schema
                ->boolean()
                ->description('Whether this is an all-day task (true) or has specific times (false).'),
            'items' => $schema
                ->string()
                ->description('JSON array of subtasks, e.g., [{"title": "Subtask 1", "status": 0}]. Status: 0 = unchecked, 2 = checked.'),
        ];
    }
}
