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

#[Name('delete_workflow')]
#[Title('Delete Workflow')]
#[Description('Discard a workflow draft entirely, freeing its id. Export it first if you want to keep the result — this cannot be undone.')]
#[IsDestructive]
final class DeleteWorkflowTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'workflow_id' => $schema->string()->description('The workflow id to delete.')->required(),
        ];
    }

    protected function act(Request $request): Response
    {
        $id = (string) $request->get('workflow_id');
        if (! $this->store->has($id)) {
            return Response::error("No workflow \"{$id}\" to delete.");
        }

        $this->store->forget($id);

        return Reply::json(['workflow_id' => $id, 'deleted' => true]);
    }
}
