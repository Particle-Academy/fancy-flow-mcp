<?php

declare(strict_types=1);

use FancyFlow\Mcp\Server\FlowBuilderServer;
use FancyFlow\Mcp\Tools\AddNodeTool;
use FancyFlow\Mcp\Tools\ConnectNodesTool;
use FancyFlow\Mcp\Tools\CreateWorkflowTool;
use FancyFlow\Mcp\Tools\DescribeNodeKindTool;
use FancyFlow\Mcp\Tools\ExportWorkflowTool;
use FancyFlow\Mcp\Tools\GetWorkflowTool;
use FancyFlow\Mcp\Tools\ListNodeKindsTool;
use FancyFlow\Mcp\Tools\RemoveNodeTool;
use FancyFlow\Mcp\Tools\RunWorkflowTool;
use FancyFlow\Mcp\Tools\ValidateWorkflowTool;

it('lists node kinds through the MCP tool', function (): void {
    FlowBuilderServer::tool(ListNodeKindsTool::class, [])
        ->assertOk()
        ->assertSee('manual_trigger')
        ->assertSee('llm_router');
});

it('describes a node kind through the MCP tool', function (): void {
    FlowBuilderServer::tool(DescribeNodeKindTool::class, ['kind' => 'branch'])
        ->assertOk()
        ->assertSee('configSchema')
        ->assertSee('condition');
});

it('errors on an unknown kind', function (): void {
    FlowBuilderServer::tool(DescribeNodeKindTool::class, ['kind' => 'nope'])
        ->assertHasErrors();
});

it('builds, validates, exports and runs a workflow end to end', function (): void {
    $id = 'wf_feature';

    FlowBuilderServer::tool(CreateWorkflowTool::class, ['workflow_id' => $id, 'name' => 'Greeter'])
        ->assertOk()
        ->assertSee($id);

    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'manual_trigger', 'node_id' => 'start'])
        ->assertOk()
        ->assertSee('start');

    FlowBuilderServer::tool(AddNodeTool::class, [
        'workflow_id' => $id, 'kind' => 'transform', 'node_id' => 't',
        'config' => ['expression' => '{{ $json }}'],
    ])->assertOk();

    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'output', 'node_id' => 'end'])
        ->assertOk();

    FlowBuilderServer::tool(ConnectNodesTool::class, ['workflow_id' => $id, 'source' => 'start', 'target' => 't'])
        ->assertOk();
    FlowBuilderServer::tool(ConnectNodesTool::class, ['workflow_id' => $id, 'source' => 't', 'target' => 'end'])
        ->assertOk();

    FlowBuilderServer::tool(GetWorkflowTool::class, ['workflow_id' => $id])
        ->assertOk()
        ->assertSee('"node_count": 3');

    FlowBuilderServer::tool(ValidateWorkflowTool::class, ['workflow_id' => $id])
        ->assertOk()
        ->assertSee('"ok": true');

    FlowBuilderServer::tool(ExportWorkflowTool::class, ['workflow_id' => $id])
        ->assertOk()
        ->assertSee('@particle-academy/manual_trigger');

    FlowBuilderServer::tool(RunWorkflowTool::class, [
        'workflow_id' => $id,
        'initial_inputs' => ['start' => ['payload' => ['q' => 'hi']]],
    ])->assertOk()->assertSee('"ok": true');
});

it('rejects an invalid connection with a helpful message', function (): void {
    $id = 'wf_ports';
    FlowBuilderServer::tool(CreateWorkflowTool::class, ['workflow_id' => $id])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'branch', 'node_id' => 'b'])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'output', 'node_id' => 'end'])->assertOk();

    // No source_handle → default "out" is not a branch port → error naming the real ports.
    FlowBuilderServer::tool(ConnectNodesTool::class, ['workflow_id' => $id, 'source' => 'b', 'target' => 'end'])
        ->assertHasErrors();

    // With the right handle it succeeds.
    FlowBuilderServer::tool(ConnectNodesTool::class, [
        'workflow_id' => $id, 'source' => 'b', 'target' => 'end', 'source_handle' => 'true',
    ])->assertOk();
});

it('removes a node and cascades its edges through the tool', function (): void {
    $id = 'wf_remove';
    FlowBuilderServer::tool(CreateWorkflowTool::class, ['workflow_id' => $id])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'manual_trigger', 'node_id' => 'start'])->assertOk();
    FlowBuilderServer::tool(AddNodeTool::class, ['workflow_id' => $id, 'kind' => 'output', 'node_id' => 'end'])->assertOk();
    FlowBuilderServer::tool(ConnectNodesTool::class, ['workflow_id' => $id, 'source' => 'start', 'target' => 'end'])->assertOk();

    FlowBuilderServer::tool(RemoveNodeTool::class, ['workflow_id' => $id, 'node_id' => 'start'])
        ->assertOk()
        ->assertSee('removed_edges');

    FlowBuilderServer::tool(GetWorkflowTool::class, ['workflow_id' => $id])
        ->assertOk()
        ->assertSee('"edge_count": 0');
});

it('errors when the workflow id is unknown', function (): void {
    FlowBuilderServer::tool(GetWorkflowTool::class, ['workflow_id' => 'does_not_exist'])
        ->assertHasErrors();
});
