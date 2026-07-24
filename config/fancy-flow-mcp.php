<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Local (stdio) server
    |--------------------------------------------------------------------------
    | Register the Fancy Flow Builder as a local MCP server, runnable via
    | `php artisan mcp:start <name>` and connectable from a desktop AI client.
    | This is the primary way to let an agent build workflows on your machine.
    */
    'local' => [
        'enabled' => env('FANCY_FLOW_MCP_LOCAL', true),
        'name' => env('FANCY_FLOW_MCP_LOCAL_NAME', 'fancy-flow'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Web (HTTP) server
    |--------------------------------------------------------------------------
    | Opt in by setting a path (e.g. '/mcp/fancy-flow'); the server is then
    | exposed over HTTP POST. Protect it with middleware — an MCP endpoint runs
    | tools on your app. Left null, no web route is registered.
    */
    'web' => [
        'path' => env('FANCY_FLOW_MCP_WEB_PATH'),
        'middleware' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Draft store
    |--------------------------------------------------------------------------
    | Where in-progress workflows live between tool calls. 'cache' survives the
    | request-per-call lifecycle of a web server; 'array' keeps them in the
    | current process (fine for a long-lived local/stdio server). For 'cache',
    | `store` names a cache store (null = default) and `ttl` is seconds (null =
    | forever).
    */
    'store' => [
        'driver' => env('FANCY_FLOW_MCP_STORE', 'cache'),
        'cache_store' => env('FANCY_FLOW_MCP_CACHE_STORE'),
        'prefix' => 'fancy-flow-mcp:draft:',
        'ttl' => null,
    ],

];
