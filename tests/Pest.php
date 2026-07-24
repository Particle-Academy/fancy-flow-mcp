<?php

declare(strict_types=1);

/*
 * Unit tests (tests/Unit) exercise the framework-free authoring core directly —
 * no Laravel needed. Feature tests (tests/Feature) drive the actual MCP tools
 * through the FlowBuilderServer using Orchestra Testbench.
 */

use FancyFlow\Mcp\Tests\TestCase;

uses(TestCase::class)->in('Feature');
