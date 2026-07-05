<?php

namespace App\Mcp\Tools;

use App\Models\MockResponse;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create or update the mock response for an endpoint. Admin access is required.')]
class UpsertMockResponseTool extends AdminTool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'endpointId' => ['required', 'integer', 'exists:endpoints,id'],
            'statusCode' => ['sometimes', 'integer', 'min:100', 'max:599'],
            'body' => ['nullable', 'array'],
            'headers' => ['nullable', 'array'],
            'headers.*' => ['string'],
            'delayMs' => ['sometimes', 'integer', 'min:0', 'max:30000'],
        ], [
            'endpointId.exists' => 'The requested endpoint does not exist. Create it first or choose an existing endpoint id.',
            'headers.*.string' => 'Every response header value must be a string.',
        ]);

        $mockResponse = MockResponse::where('endpoint_id', $validated['endpointId'])->first();
        $created = $mockResponse === null;
        $attributes = $this->responseAttributes($request, $validated);

        if ($mockResponse) {
            $mockResponse->update($attributes);
        } else {
            $mockResponse = MockResponse::create([
                'endpoint_id' => $validated['endpointId'],
                'status_code' => 200,
                'body' => null,
                'headers' => null,
                'delay_ms' => 0,
                ...$attributes,
            ]);
        }

        return Response::json([
            'action' => $created ? 'created' : 'updated',
            'mockResponseId' => $mockResponse->id,
            'endpointId' => $mockResponse->endpoint_id,
            'statusCode' => $mockResponse->status_code,
            'body' => $mockResponse->body,
            'headers' => $mockResponse->headers,
            'delayMs' => $mockResponse->delay_ms,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function responseAttributes(Request $request, array $validated): array
    {
        $arguments = $request->all();
        $attributes = [];

        foreach ([
            'statusCode' => 'status_code',
            'body' => 'body',
            'headers' => 'headers',
            'delayMs' => 'delay_ms',
        ] as $argument => $attribute) {
            if (array_key_exists($argument, $arguments)) {
                $attributes[$attribute] = $validated[$argument];
            }
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'endpointId' => $schema->integer()
                ->description('Endpoint id whose mock response should be created or updated.')
                ->required(),
            'statusCode' => $schema->integer()
                ->description('HTTP response status code from 100 to 599.')
                ->default(200),
            'body' => $schema->object()
                ->description('Optional JSON response body.')
                ->nullable(),
            'headers' => $schema->object()
                ->description('Optional response headers as string key-value pairs.')
                ->nullable(),
            'delayMs' => $schema->integer()
                ->description('Artificial response delay in milliseconds, from 0 to 30000.')
                ->default(0),
        ];
    }
}
