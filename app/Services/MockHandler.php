<?php

namespace App\Services;

use App\Models\Endpoint;
use App\Services\Contracts\RequestHandlerInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MockHandler implements RequestHandlerInterface
{
    public function handle(Request $request, Endpoint $endpoint): Response
    {
        $mock = $endpoint->mockResponse;

        // If no mock response is configured, return a sensible default
        if (! $mock) {
            return new JsonResponse(
                ['error' => 'No mock response configured for this endpoint.'],
                Response::HTTP_NOT_IMPLEMENTED,
            );
        }

        // Simulate network/processing delay
        if ($mock->delay_ms > 0) {
            usleep($mock->delay_ms * 1000);
        }

        // Build headers — always include Content-Type if not explicitly set
        $headers = array_merge(
            ['Content-Type' => 'application/json'],
            $mock->headers ?? [],
        );

        // body is stored as array; re-encode to JSON string for the response
        $body = $mock->body !== null
            ? json_encode($mock->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';

        return new Response($body, $mock->status_code, $headers);
    }
}
