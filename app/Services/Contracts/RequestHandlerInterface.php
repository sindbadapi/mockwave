<?php

namespace App\Services\Contracts;

use App\Models\Endpoint;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface RequestHandlerInterface
{
    /**
     * Handle an incoming gateway request and return an HTTP response.
     *
     * @param  Request  $request  The incoming client request
     * @param  Endpoint  $endpoint  Matched endpoint configuration
     */
    public function handle(Request $request, Endpoint $endpoint): Response;
}
