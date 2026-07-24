<?php

declare(strict_types=1);

namespace FancyFlow\Mcp\Server;

use FancyFlow\Mcp\Resources\NodeKindsResource;
use FancyFlow\Mcp\Tools\AddNodeTool;
use FancyFlow\Mcp\Tools\ConfigureNodeTool;
use FancyFlow\Mcp\Tools\ConnectNodesTool;
use FancyFlow\Mcp\Tools\CreateWorkflowTool;
use FancyFlow\Mcp\Tools\DeleteWorkflowTool;
use FancyFlow\Mcp\Tools\DescribeNodeKindTool;
use FancyFlow\Mcp\Tools\ExportWorkflowTool;
use FancyFlow\Mcp\Tools\GetWorkflowTool;
use FancyFlow\Mcp\Tools\ImportWorkflowTool;
use FancyFlow\Mcp\Tools\ListNodeKindsTool;
use FancyFlow\Mcp\Tools\ListWorkflowsTool;
use FancyFlow\Mcp\Tools\RemoveEdgeTool;
use FancyFlow\Mcp\Tools\RemoveNodeTool;
use FancyFlow\Mcp\Tools\RunWorkflowTool;
use FancyFlow\Mcp\Tools\ValidateWorkflowTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * The Fancy Flow Builder MCP server — the single entry point an AI agent
 * connects to in order to author fancy-flow workflows headlessly. It exposes the
 * full build cycle as tools (discover kinds → create → add/connect/configure →
 * validate → export → run) plus the node-kind catalogue as a resource.
 *
 * Every tool is a thin, deterministic wrapper over particle-academy/fancy-flow-php;
 * the connecting model is the only intelligence involved — this server makes no
 * LLM calls of its own.
 */
#[Name('Fancy Flow Builder')]
#[Version('0.1.0')]
#[Instructions(<<<'TXT'
This server lets you build fancy-flow workflows headlessly, as portable
WorkflowSchema v1 documents.

Typical flow:
  1. list_node_kinds (and describe_node_kind) to learn the available kinds,
     their config fields, and their ports.
  2. create_workflow to start a draft and get a workflow_id.
  3. add_node for each step; connect_nodes to wire output ports to input ports
     (connections are port-validated — a branch needs source_handle "true" or
     "false", a merge needs target_handle "a" or "b").
  4. configure_node to fill in each node's config (schema-checked).
  5. validate_workflow for the authoritative check (unknown kinds, missing
     required config, dangling edges) plus host capability status.
  6. export_workflow for the final WorkflowSchema JSON — the deliverable that
     <FlowEditor> imports and both the Node and PHP engines run unchanged.
  7. run_workflow to smoke-test wiring with deterministic fake executors.

Pass the workflow_id returned by create_workflow to every subsequent call.
TXT)]
final class FlowBuilderServer extends Server
{
    /** @var array<int, class-string<\Laravel\Mcp\Server\Tool>> */
    protected array $tools = [
        // discover
        ListNodeKindsTool::class,
        DescribeNodeKindTool::class,
        // lifecycle
        CreateWorkflowTool::class,
        ImportWorkflowTool::class,
        GetWorkflowTool::class,
        ListWorkflowsTool::class,
        DeleteWorkflowTool::class,
        // author
        AddNodeTool::class,
        ConfigureNodeTool::class,
        ConnectNodesTool::class,
        RemoveNodeTool::class,
        RemoveEdgeTool::class,
        // finish
        ValidateWorkflowTool::class,
        ExportWorkflowTool::class,
        RunWorkflowTool::class,
    ];

    /** @var array<int, class-string<\Laravel\Mcp\Server\Resource>> */
    protected array $resources = [
        NodeKindsResource::class,
    ];
}
