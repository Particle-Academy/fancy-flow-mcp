<?php

declare(strict_types=1);

/**
 * An authored node carries no sampling parameter unless the agent sets one.
 *
 * `add_node` seeds a node from fancy-flow-php's `defaultConfigFor()`, and its
 * `llm_call` and `agent` kinds declared `temperature` with a 0.7 default. So
 * every LLM node this server created carried `temperature: 0.7`, the host's
 * client sent it, and current Anthropic models reject a request that sets it:
 * a flow built here failed on its first model call.
 *
 * The default lives in fancy-flow-php and is removed there; this server has
 * none of its own. These tests pin what an agent sees through the real tools.
 */

use FancyFlow\Mcp\Server\FlowBuilderServer;
use FancyFlow\Mcp\Tools\AddNodeTool;
use FancyFlow\Mcp\Tools\CreateWorkflowTool;
use FancyFlow\Mcp\Tools\DescribeNodeKindTool;
use FancyFlow\Mcp\Tools\ExportWorkflowTool;

it('adds an llm_call with no temperature', function (): void {
    $id = 'wf_sampling';
    FlowBuilderServer::tool(CreateWorkflowTool::class, ['workflow_id' => $id])->assertOk();

    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'llm_call', 'node_id' => 'llm'])
        ->assertOk()
        // The kind's other defaults still arrive: this is not "no defaults".
        ->assertSee('"max_tokens": 1024')
        ->assertDontSee('temperature');

    FlowBuilderServer::tool(ExportWorkflowTool::class, ['workflow_id' => $id])
        ->assertOk()
        ->assertDontSee('temperature');
});

it('describes llm_call and agent with no sampling default', function (string $kind): void {
    FlowBuilderServer::tool(DescribeNodeKindTool::class, ['kind' => $kind])
        ->assertOk()
        // The field is still declared, so an agent can set it ...
        ->assertSee('temperature')
        // ... but no default for it is offered anywhere in the manifest.
        ->assertDontSee('0.7');
})->with(['llm_call', 'agent']);

it('carries an explicit temperature unchanged', function (): void {
    $id = 'wf_sampling_explicit';
    FlowBuilderServer::tool(CreateWorkflowTool::class, ['workflow_id' => $id])->assertOk();

    FlowBuilderServer::tool(AddNodeTool::class, [
        'workflow_id' => $id, 'kind' => 'llm_call', 'node_id' => 'llm',
        'config' => ['model' => 'm', 'prompt' => 'p', 'temperature' => 0.2],
    ])->assertOk()->assertSee('"temperature": 0.2');
});
