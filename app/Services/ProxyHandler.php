<?php

namespace App\Services;

use App\Models\Endpoint;
use App\Services\Contracts\RequestHandlerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProxyHandler implements RequestHandlerInterface
{
    public function __construct(private readonly Client $httpClient) {}

    public function handle(Request $request, Endpoint $endpoint): Response
    {
        $targetUrl = $this->resolveTargetUrl($request, $endpoint);

        $options = [
            'timeout' => config('gateway.timeout_seconds', 30),
            'allow_redirects' => true,
            'http_errors' => false, // we handle non-2xx ourselves
            'headers' => $this->forwardHeaders($request),
            'query' => $request->query->all(),
        ];

        // Forward body for methods that carry payload
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $options['body'] = $request->getContent();
        }

        try {
            $upstream = $this->httpClient->request($request->method(), $targetUrl, $options);
        } catch (ConnectException $e) {
            return new Response(
                json_encode(['error' => 'Upstream service unavailable.', 'detail' => $e->getMessage()]),
                Response::HTTP_BAD_GATEWAY,
                ['Content-Type' => 'application/json'],
            );
        } catch (RequestException $e) {
            return new Response(
                json_encode(['error' => 'Proxy request failed.', 'detail' => $e->getMessage()]),
                Response::HTTP_BAD_GATEWAY,
                ['Content-Type' => 'application/json'],
            );
        }

        // Map Guzzle response → Symfony Response
        $responseHeaders = [];
        foreach ($upstream->getHeaders() as $name => $values) {
            // Skip hop-by-hop headers that must not be forwarded
            if ($this->isHopByHopHeader($name)) {
                continue;
            }
            $responseHeaders[$name] = implode(', ', $values);
        }

        return new Response(
            (string) $upstream->getBody(),
            $upstream->getStatusCode(),
            $responseHeaders,
        );
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function resolveTargetUrl(Request $request, Endpoint $endpoint): string
    {
        // Endpoint-level proxy_url takes priority; fall back to service base_url
        $base = $endpoint->proxy_url
            ?? rtrim($endpoint->service->base_url ?? '', '/');

        if (empty($base)) {
            abort(Response::HTTP_BAD_GATEWAY, 'No proxy URL configured for this endpoint.');
        }

        // Append the original request path after the gateway prefix
        // e.g. /gateway/bank-api/v1/accounts → /v1/accounts appended to base
        return $base.'/'.ltrim($endpoint->path, '/');
    }

    /** @return array<string, string> */
    private function forwardHeaders(Request $request): array
    {
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            if ($this->isHopByHopHeader($name) || strtolower($name) === 'host') {
                continue;
            }
            $headers[$name] = implode(', ', $values);
        }

        // Identify ourselves as a proxy
        $headers['X-Forwarded-By'] = 'Mockwave';
        $headers['X-Forwarded-For'] = $request->ip();

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
        ]);
    }
}
