<?php

use App\Mcp\Servers\MockwaveServer;
use Laravel\Mcp\Facades\Mcp;

// auth:sanctum is sufficient for Mockwave's admin-only MCP access.
Mcp::web('/mcp', MockwaveServer::class)->middleware(['auth:sanctum']);
