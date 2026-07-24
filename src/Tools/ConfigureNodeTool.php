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

#[Name('configure_node')]
#[Title('Configure Node')]
#[Description('Set a node\'s config, schema-checked against its kind. By default the given keys are merged over the existing config; pass merge=false to replace it wholesale. Returns the resulting config plus any validation issues (wrong type, missing required) — issues are reported, not rejected, so partial progress is kept. See describe_node_kind for the fields a kind accepts.')]
final class ConfigureNodeTool extends FlowTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'workflow_id' => $schema->string()->description('The workflow id.')->required(),
            'node_id' => $schema->string()->description('The node to configure.')->required(),
            'config' => $schema->object()->description('Config keys/values to apply.')->required(),
            'merge' => $schema->boolean()->description('Merge over existing config (default true) or replace it (false).')->default(true),
        ];
    }

    protected function act(Request $request): Response
    {
        $draft = $this->draftOrFail((string) $request->get('workflow_id'));

        $config = $this->objectArg($request, 'config');
        if ($config === null) {
            return Response::error('The `config` argument must be an object of config keys/values.');
        }

        $merge = $request->get('merge');
        $merge = is_bool($merge) ? $merge : true;

        $result = $this->authoring->configureNode(
            $draft,
            nodeId: (string) $request->get('node_id'),
            config: $config,
            merge: $merge,
        );
        $this->store->put($draft);

        return Reply::json([
            'workflow_id' => $draft->id,
            'node_id' => (string) $request->get('node_id'),
            'ok' => $result['ok'],
            'config' => $result['config'],
            'config_issues' => $result['issues'],
        ]);
    }
}
