<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEndpointRequest;
use App\Http\Requests\Admin\UpdateEndpointRequest;
use App\Http\Resources\EndpointResource;
use App\Models\Endpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EndpointController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $endpoints = Endpoint::with(['service', 'mockResponse'])
            ->when($request->service_id, fn ($q) => $q->where('service_id', $request->service_id))
            ->orderBy('path')
            ->paginate(50);

        return EndpointResource::collection($endpoints);
    }

    public function store(StoreEndpointRequest $request): EndpointResource
    {
        $endpoint = Endpoint::create($request->validated());
        $endpoint->load(['service', 'mockResponse']);

        return new EndpointResource($endpoint);
    }

    public function show(Endpoint $endpoint): EndpointResource
    {
        $endpoint->load(['service', 'mockResponse']);

        return new EndpointResource($endpoint);
    }

    public function update(UpdateEndpointRequest $request, Endpoint $endpoint): EndpointResource
    {
        $endpoint->update($request->validated());
        $endpoint->load(['service', 'mockResponse']);

        return new EndpointResource($endpoint);
    }

    public function destroy(Endpoint $endpoint): JsonResponse
    {
        $endpoint->delete();

        return response()->json(['message' => 'Endpoint deleted.']);
    }
}
