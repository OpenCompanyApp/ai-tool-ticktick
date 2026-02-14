<?php

namespace OpenCompany\AiToolTickTick\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolTickTick\TickTickService;

class TickTickCreateProject implements Tool
{
    public function __construct(
        private TickTickService $service,
    ) {}

    public function description(): string
    {
        return 'Create a new TickTick project (task list).';
    }

    public function handle(Request $request): string
    {
        try {
            if (! $this->service->isConfigured()) {
                return 'Error: TickTick integration is not configured.';
            }

            $data = ['name' => $request['name']];

            if (isset($request['color'])) {
                $data['color'] = $request['color'];
            }
            if (isset($request['viewMode'])) {
                $data['viewMode'] = $request['viewMode'];
            }

            $result = $this->service->createProject($data);

            return "Project '{$request['name']}' created successfully.\n" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return "Error creating TickTick project: {$e->getMessage()}";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema
                ->string()
                ->description('Name of the project.')
                ->required(),
            'color' => $schema
                ->string()
                ->description('Color of the project (e.g., "#ff8c00").'),
            'viewMode' => $schema
                ->string()
                ->description('View mode: "list", "kanban", or "timeline".'),
        ];
    }
}
