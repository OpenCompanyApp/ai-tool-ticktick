<?php

namespace OpenCompany\AiToolTickTick\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolTickTick\TickTickService;

class TickTickDeleteProject implements Tool
{
    public function __construct(
        private TickTickService $service,
    ) {}

    public function description(): string
    {
        return 'Delete a TickTick project and all its tasks. This action cannot be undone.';
    }

    public function handle(Request $request): string
    {
        try {
            if (! $this->service->isConfigured()) {
                return 'Error: TickTick integration is not configured.';
            }

            $this->service->deleteProject($request['projectId']);

            return "Project '{$request['projectId']}' deleted successfully.";
        } catch (\Throwable $e) {
            return "Error deleting TickTick project: {$e->getMessage()}";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'projectId' => $schema
                ->string()
                ->description('The project ID to delete. Use ticktick_list_projects to find project IDs.')
                ->required(),
        ];
    }
}
