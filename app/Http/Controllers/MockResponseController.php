<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreMockResponseRequest;
use App\Http\Resources\EndpointResource;
use App\Models\Endpoint;
use App\Models\MockResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MockResponseController extends Controller
{
    public function index(): Response
    {
        $endpoints = Endpoint::with(['service', 'mockResponse'])
            ->orderBy('path')
            ->paginate(50);

        return Inertia::render('MockResponses/Index', [
            'endpoints' => EndpointResource::collection($endpoints),
        ]);
    }

    public function store(StoreMockResponseRequest $request): RedirectResponse
    {
        // Upsert: one mock response per endpoint.
        MockResponse::updateOrCreate(
            ['endpoint_id' => $request->validated('endpoint_id')],
            $request->validated(),
        );

        return back()->with('success', 'Mock response saved.');
    }

    public function destroy(MockResponse $mockResponse): RedirectResponse
    {
        $mockResponse->delete();

        return back()->with('success', 'Mock response deleted.');
    }
}
