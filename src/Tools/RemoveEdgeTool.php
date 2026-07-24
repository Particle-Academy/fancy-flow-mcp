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
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Name('remove_edge')]
#[Title('Remove Edge')]
#[Description('Delete a single edge by its id, leaving both nodes in place. Use get_workflow to see edge ids if you need them.')]
#[IsDestructive]
final class RemoveEdgeTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'workflow_id' => $schema->string()->description('The workflow id.')->required(),
            'edge_id' => $schema->string()->description('The edge to remove.')->required(),
        ];
    }

    protected function act(Request $request): Response
    {
        $draft = $this->draftOrFail((string) $request->get('workflow_id'));

        $result = $this->authoring->removeEdge($draft, (string) $request->get('edge_id'));
        $this->store->put($draft);

        return Reply::json([
            'workflow_id' => $draft->id,
            'removed_edge' => $result['edge_id'],
        ]);
    }
}
