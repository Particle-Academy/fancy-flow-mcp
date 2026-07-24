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

#[Name('remove_node')]
#[Title('Remove Node')]
#[Description('Delete a node and every edge attached to it (in or out). Returns the ids of the edges that were removed alongside it so you know what was disconnected.')]
#[IsDestructive]
final class RemoveNodeTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'workflow_id' => $schema->string()->description('The workflow id.')->required(),
            'node_id' => $schema->string()->description('The node to remove.')->required(),
        ];
    }

    protected function act(Request $request): Response
    {
        $draft = $this->draftOrFail((string) $request->get('workflow_id'));

        $result = $this->authoring->removeNode($draft, (string) $request->get('node_id'));
        $this->store->put($draft);

        return Reply::json([
            'workflow_id' => $draft->id,
            'removed_node' => $result['node_id'],
            'removed_edges' => $result['removed_edges'],
        ]);
    }
}
