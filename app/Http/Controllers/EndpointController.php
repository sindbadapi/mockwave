<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreEndpointRequest;
use App\Http\Requests\Admin\UpdateEndpointRequest;
use App\Http\Resources\EndpointResource;
use App\Models\Endpoint;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EndpointController extends Controller
{
    public function index(Request $request): Response
    {
        $endpoints = Endpoint::with(['service', 'mockResponse'])
            ->when($request->service_id, fn ($q) => $q->where('service_id', $request->service_id))
            ->orderBy('path')
            ->paginate(50);

        return Inertia::render('Endpoints/Index', [
            'endpoints' => EndpointResource::collection($endpoints),
            'services' => Service::orderBy('name')->get(['id', 'name', 'slug', 'mode']),
            'filters' => ['service_id' => $request->input('service_id')],
        ]);
    }

    public function store(StoreEndpointRequest $request): RedirectResponse
    {
        Endpoint::create($request->validated());

        return back()->with('success', 'Endpoint created.');
    }

    public function update(UpdateEndpointRequest $request, Endpoint $endpoint): RedirectResponse
    {
        $endpoint->update($request->validated());

        return back()->with('success', 'Endpoint updated.');
    }

    public function destroy(Endpoint $endpoint): RedirectResponse
    {
        $endpoint->delete();

        return back()->with('success', 'Endpoint deleted.');
    }
}
