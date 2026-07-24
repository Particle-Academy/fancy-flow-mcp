<?php

declare(strict_types=1);

namespace FancyFlow\Mcp\Tools;

use FancyFlow\Mcp\Support\Reply;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_workflows')]
#[Title('List Workflows')]
#[Description('List every workflow draft currently held by this server — each id with its name and node/edge counts. Use it to recover a workflow_id you have lost track of.')]
#[IsReadOnly]
final class ListWorkflowsTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    protected function act(Request $request): Response
    {
        $workflows = [];
        foreach ($this->store->ids() as $id) {
            $draft = $this->store->get($id);
            if ($draft === null) {
                continue;
            }
            $workflows[] = [
                'workflow_id' => $id,
                'name' => $draft->metadata['name'] ?? null,
                'node_count' => count($draft->nodes),
                'edge_count' => count($draft->edges),
            ];
        }

        return Reply::json(['count' => count($workflows), 'workflows' => $workflows]);
    }
}
