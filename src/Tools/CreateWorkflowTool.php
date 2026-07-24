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

#[Name('create_workflow')]
#[Title('Create Workflow')]
#[Description('Start a new, empty fancy-flow workflow draft and return its id. Use that id in every subsequent add_node / connect_nodes / configure_node / validate_workflow / export_workflow call. Pass an optional name/description, or your own workflow_id for deterministic reference.')]
final class CreateWorkflowTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Human-readable workflow name.'),
            'description' => $schema->string()->description('What the workflow does.'),
            'workflow_id' => $schema->string()->description('Optional explicit id. Omit to auto-generate one.'),
        ];
    }

    protected function act(Request $request): Response
    {
        $id = $request->get('workflow_id');
        $id = is_string($id) && $id !== '' ? $id : $this->generateId();

        if ($this->store->has($id)) {
            return Response::error("A workflow with id \"{$id}\" already exists. Choose a different workflow_id or omit it.");
        }

        $name = $request->get('name');
        $description = $request->get('description');

        $draft = $this->authoring->create(
            $id,
            is_string($name) && $name !== '' ? $name : null,
            is_string($description) && $description !== '' ? $description : null,
        );
        $this->store->put($draft);

        return Reply::json([
            'workflow_id' => $id,
            'created' => true,
            'metadata' => $draft->metadata,
            'next' => 'Add nodes with add_node, wire them with connect_nodes, then validate_workflow and export_workflow.',
        ]);
    }

    private function generateId(): string
    {
        return 'wf_'.bin2hex(random_bytes(6));
    }
}
