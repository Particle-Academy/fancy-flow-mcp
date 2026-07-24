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

#[Name('import_workflow')]
#[Title('Import Workflow')]
#[Description('Load an existing WorkflowSchema v1 document (as authored in <FlowEditor> or exported from fancy-flow) into a new editable draft, and return its id plus any import issues. Import is lenient — unknown-kind or dangling-edge problems are reported, not rejected, so you can repair the graph and re-validate.')]
final class ImportWorkflowTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'schema' => $schema->object()
                ->description('A WorkflowSchema v1 document ({ version, graph: { nodes, edges }, metadata? }).')
                ->required(),
            'workflow_id' => $schema->string()->description('Optional explicit id for the new draft.'),
            'name' => $schema->string()->description('Optional name override for the draft.'),
        ];
    }

    protected function act(Request $request): Response
    {
        $schema = $request->get('schema');
        if (! is_array($schema)) {
            return Response::error('The `schema` argument must be a WorkflowSchema v1 object.');
        }

        $id = $request->get('workflow_id');
        $id = is_string($id) && $id !== '' ? $id : 'wf_'.bin2hex(random_bytes(6));
        if ($this->store->has($id)) {
            return Response::error("A workflow with id \"{$id}\" already exists. Choose a different workflow_id or omit it.");
        }

        $name = $request->get('name');
        $result = $this->authoring->import($id, $schema, is_string($name) && $name !== '' ? $name : null);

        /** @var \FancyFlow\Mcp\Support\DraftWorkflow $draft */
        $draft = $result['draft'];
        $this->store->put($draft);

        return Reply::json([
            'workflow_id' => $id,
            'imported' => true,
            'ok' => $result['ok'],
            'node_count' => count($draft->nodes),
            'edge_count' => count($draft->edges),
            'issues' => $result['issues'],
        ]);
    }
}
