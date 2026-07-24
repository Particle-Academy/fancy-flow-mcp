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

#[Name('add_node')]
#[Title('Add Node')]
#[Description('Add a node of a given kind to a workflow. The kind is validated against the registry (call list_node_kinds first). If you omit config, the kind\'s schema defaults are applied; if you pass config it is schema-checked and any issues (e.g. missing required fields) are returned — non-fatal, so you can fill them in later with configure_node. Returns the created node, including its assigned node_id.')]
final class AddNodeTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'workflow_id' => $schema->string()->description('The workflow id from create_workflow.')->required(),
            'kind' => $schema->string()->description('Node kind, e.g. "manual_trigger", "llm_call", "branch".')->required(),
            'node_id' => $schema->string()->description('Optional explicit node id. Omit to auto-generate (e.g. "branch-1").'),
            'label' => $schema->string()->description('Optional display label for the node.'),
            'position' => $schema->object()->description('Optional canvas position { "x": number, "y": number }.'),
            'config' => $schema->object()->description('Optional config object for the node. Omit to use the kind defaults. See describe_node_kind for the fields.'),
        ];
    }

    protected function act(Request $request): Response
    {
        $draft = $this->draftOrFail((string) $request->get('workflow_id'));

        $result = $this->authoring->addNode(
            $draft,
            kind: (string) $request->get('kind'),
            nodeId: $this->stringOrNull($request->get('node_id')),
            label: $this->stringOrNull($request->get('label')),
            position: $this->objectArg($request, 'position'),
            config: $this->objectArg($request, 'config'),
        );
        $this->store->put($draft);

        return Reply::json([
            'workflow_id' => $draft->id,
            'node' => $result['node'],
            'config_issues' => $result['issues'],
        ]);
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
