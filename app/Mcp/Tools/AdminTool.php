<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Tool;

abstract class AdminTool extends Tool
{
    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user !== null
            && $user->isAdmin()
            && $user->tokenCan('mcp:write');
    }
}
