<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use App\Models\RequestLog;
use App\Models\Service;
use App\Services\MockHandler;
use App\Services\ProxyHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

class MockGatewayController extends Controller
{
    public function __construct(
        private readonly MockHandler $mockHandler,
        private readonly ProxyHandler $proxyHandler,
    ) {}

    /**
     * Unified entry point for all gateway traffic.
     *
     * Route: {METHOD} /gateway/{service_slug}/{path?}
     */
    public function handle(Request $request, string $serviceSlug, string $path = ''): Response
    {
        $startedAt = hrtime(true);

        // ── 1. Resolve service ────────────────────────────────────────────────
        $service = Service::where('slug', $serviceSlug)
            ->where('is_active', true)
            ->first();

        if (! $service) {
            return $this->logAndRespond(
                request: $request,
                endpoint: null,
                mode: 'not_found',
                duration: $this->elapsedMs($startedAt),
                response: $this->notFoundResponse("Service '{$serviceSlug}' not found."),
                path: $path,
            );
        }

        // ── 2. Resolve endpoint ───────────────────────────────────────────────
        $endpoint = $this->resolveEndpoint($service->id, $request->method(), $path);

        if (! $endpoint) {
            return $this->logAndRespond(
                request: $request,
                endpoint: null,
                mode: 'not_found',
                duration: $this->elapsedMs($startedAt),
                response: $this->notFoundResponse(
                    "No active endpoint for {$request->method()} /{$path} in service '{$serviceSlug}'."
                ),
                path: $path,
            );
        }

        // ── 3. Pick handler based on resolved mode ────────────────────────────
        $mode = $endpoint->resolvedMode();
        $handler = $mode === 'proxy' ? $this->proxyHandler : $this->mockHandler;

        try {
            $response = $handler->handle($request, $endpoint);
        } catch (\Throwable $e) {
            Log::error('[Gateway] Handler exception', [
                'service' => $serviceSlug,
                'path' => $path,
                'mode' => $mode,
                'error' => $e->getMessage(),
            ]);

            $response = new Response(
                json_encode(['error' => 'Internal gateway error.', 'detail' => $e->getMessage()]),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['Content-Type' => 'application/json'],
            );
        }

        // ── 4. Log & return ───────────────────────────────────────────────────
        return $this->logAndRespond(
            request: $request,
            endpoint: $endpoint,
            mode: $mode,
            duration: $this->elapsedMs($startedAt),
            response: $response,
            path: $path,
        );
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Find the best matching active endpoint for the given method + path.
     *
     * Priority:
     *   1. Exact method + path match
     *   2. ANY method + exact path match
     */
    private function resolveEndpoint(int $serviceId, string $method, string $path): ?Endpoint
    {
        $normalised = '/'.ltrim($path, '/');

        return Endpoint::with(['service', 'mockResponse'])
            ->where('service_id', $serviceId)
            ->where('is_active', true)
            ->where('path', $normalised)
            ->orderByRaw('CASE WHEN method = ? THEN 0 ELSE 1 END', [$method])
            ->whereIn('method', [$method, 'ANY'])
            ->first();
    }

    /**
     * Persist a RequestLog entry and return the response untouched.
     */
    private function logAndRespond(
        Request $request,
        ?Endpoint $endpoint,
        string $mode,
        int $duration,
        Response $response,
        string $path,
    ): Response {
        $maxSize = config('gateway.max_log_body_size', 65536);
        $requestData = config('gateway.log_request_body')
            ? [
                'headers' => $request->headers->all(),
                'query' => $request->query->all(),
                'body' => $this->truncate($request->getContent(), $maxSize),
            ]
            : null;
        $responseData = config('gateway.log_response_body')
            ? [
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
                'body' => $this->truncate($response->getContent(), $maxSize),
            ]
            : null;
        $requestMethod = $request->method();

        defer(function () use ($endpoint, $requestMethod, $path, $mode, $duration, $requestData, $responseData): void {
            try {
                RequestLog::create([
                    'endpoint_id' => $endpoint?->id,
                    'method' => $requestMethod,
                    'path' => '/'.ltrim($path, '/'),
                    'request_data' => $requestData,
                    'response_data' => $responseData,
                    'mode_used' => $mode,
                    'duration_ms' => $duration,
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('[Gateway] Failed to write request log: '.$e->getMessage());
            }
        })->always();

        return $response;
    }

    private function truncate(string $content, int $maxBytes): string
    {
        if ($maxBytes === 0 || strlen($content) <= $maxBytes) {
            return $content;
        }

        return substr($content, 0, $maxBytes).'…[truncated]';
    }

    private function elapsedMs(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }

    private function notFoundResponse(string $message): Response
    {
        return new Response(
            json_encode(['error' => $message]),
            Response::HTTP_NOT_FOUND,
            ['Content-Type' => 'application/json'],
        );
    }
}
