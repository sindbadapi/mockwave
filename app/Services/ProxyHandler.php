<?php

namespace App\Services;

use App\Models\Endpoint;
use App\Services\Contracts\RequestHandlerInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ProxyHandler implements RequestHandlerInterface
{
    public function handle(Request $request, Endpoint $endpoint): Response
    {
        $targetUrl = $this->resolveTargetUrl($endpoint);

        $pending = Http::withHeaders($this->forwardHeaders($request))
            ->withQueryParameters($request->query->all())
            ->timeout(config('gateway.timeout_seconds', 30))
            ->connectTimeout(10)
            ->withOptions(['allow_redirects' => true]);

        // Forward body (and its content type) for methods that carry payload.
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $pending = $pending->withBody(
                $request->getContent(),
                $request->header('Content-Type', 'application/json'),
            );
        }

        try {
            $upstream = $pending->send($request->method(), $targetUrl);
        } catch (ConnectionException $e) {
            return new Response(
                json_encode(['error' => 'Upstream service unavailable.', 'detail' => $e->getMessage()]),
                Response::HTTP_BAD_GATEWAY,
                ['Content-Type' => 'application/json'],
            );
        }

        // Map Laravel HTTP client response → Symfony Response, dropping hop-by-hop headers.
        $responseHeaders = [];
        foreach ($upstream->headers() as $name => $values) {
            if ($this->isHopByHopHeader($name)) {
                continue;
            }
            $responseHeaders[$name] = implode(', ', (array) $values);
        }

        return new Response($upstream->body(), $upstream->status(), $responseHeaders);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function resolveTargetUrl(Endpoint $endpoint): string
    {
        // Endpoint-level proxy_url takes priority; fall back to service base_url.
        $base = $endpoint->proxy_url
            ?? rtrim($endpoint->service->base_url ?? '', '/');

        if (empty($base)) {
            abort(Response::HTTP_BAD_GATEWAY, 'No proxy URL configured for this endpoint.');
        }

        // Append the endpoint path after the upstream base.
        // e.g. /gateway/bank-api/v1/accounts → {base}/v1/accounts
        return rtrim($base, '/').'/'.ltrim($endpoint->path, '/');
    }

    /** @return array<string, string> */
    private function forwardHeaders(Request $request): array
    {
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            // Skip hop-by-hop, host (set by the client), content-length (recomputed),
            // and content-type (owned by withBody to avoid duplication).
            if ($this->isHopByHopHeader($name)
                || in_array(strtolower($name), ['host', 'content-length', 'content-type'], true)) {
                continue;
            }
            $headers[$name] = implode(', ', $values);
        }

        // Identify ourselves as a proxy.
        $headers['X-Forwarded-By'] = 'Mockwave';
        $headers['X-Forwarded-For'] = $request->ip() ?? '';

        return $headers;
    }

    /**
     * Hop-by-hop headers must not be forwarded between connections (RFC 2616 §13.5.1).
     */
    private function isHopByHopHeader(string $name): bool
    {
        return in_array(strtolower($name), [
            'connection', 'keep-alive', 'proxy-authenticate', 'proxy-authorization',
            'te', 'trailers', 'transfer-encoding', 'upgrade',
        ], true);
    }
}
