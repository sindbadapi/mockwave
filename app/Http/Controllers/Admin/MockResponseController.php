<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMockResponseRequest;
use App\Http\Requests\Admin\UpdateMockResponseRequest;
use App\Http\Resources\MockResponseResource;
use App\Models\MockResponse;
use Illuminate\Http\JsonResponse;

class MockResponseController extends Controller
{
    public function show(MockResponse $mockResponse): MockResponseResource
    {
        return new MockResponseResource($mockResponse->load('endpoint'));
    }

    public function store(StoreMockResponseRequest $request): MockResponseResource
    {
        // Upsert: one mock response per endpoint
        $mockResponse = MockResponse::updateOrCreate(
            ['endpoint_id' => $request->validated('endpoint_id')],
            $request->validated(),
        );

        return new MockResponseResource($mockResponse);
    }

    public function update(UpdateMockResponseRequest $request, MockResponse $mockResponse): MockResponseResource
    {
        $mockResponse->update($request->validated());

        return new MockResponseResource($mockResponse);
    }

    public function destroy(MockResponse $mockResponse): JsonResponse
    {
        $mockResponse->delete();

        return response()->json(['message' => 'Mock response deleted.']);
    }
}
