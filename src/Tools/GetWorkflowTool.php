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

#[Name('get_workflow')]
#[Title('Get Workflow')]
#[Description('Inspect the current state of a workflow draft — its metadata plus every node (id, kind, label, config) and edge (id, source/target and their handles). Use it to see what you have built so far before adding, connecting, or removing.')]
#[IsReadOnly]
final class GetWorkflowTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'workflow_id' => $schema->string()->description('The workflow id from create_workflow.')->required(),
        ];
    }

    protected function act(Request $request): Response
    {
        $draft = $this->draftOrFail((string) $request->get('workflow_id'));

        return Reply::json([
            'workflow_id' => $draft->id,
            'metadata' => $draft->metadata,
            'node_count' => count($draft->nodes),
            'edge_count' => count($draft->edges),
            'nodes' => $draft->nodes,
            'edges' => $draft->edges,
        ]);
    }
}
