<?php

namespace OpenCompany\AiToolTickTick\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use OpenCompany\AiToolTickTick\TickTickService;

class TickTickListProjects implements Tool
{
    public function __construct(
        private TickTickService $service,
    ) {}

    public function description(): string
    {
        return 'List all TickTick projects (task lists). Returns project names, IDs, and metadata. Use this first to discover available projects before working with tasks.';
    }

    public function handle(Request $request): string
    {
        try {
            if (! $this->service->isConfigured()) {
                return 'Error: TickTick integration is not configured.';
            }

            $projects = $this->service->listProjects();

            if (empty($projects)) {
                return 'No projects found in your TickTick account.';
            }

            return json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return "Error listing TickTick projects: {$e->getMessage()}";
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
