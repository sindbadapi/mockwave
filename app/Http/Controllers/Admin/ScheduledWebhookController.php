<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScheduledWebhookRequest;
use App\Http\Requests\Admin\UpdateScheduledWebhookRequest;
use App\Http\Resources\ScheduledWebhookResource;
use App\Models\ScheduledWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ScheduledWebhookController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ScheduledWebhookResource::collection(
            ScheduledWebhook::orderBy('name')->paginate(25)
        );
    }

    public function store(StoreScheduledWebhookRequest $request): ScheduledWebhookResource
    {
        return new ScheduledWebhookResource(ScheduledWebhook::create($request->validated()));
    }

    public function show(ScheduledWebhook $scheduledWebhook): ScheduledWebhookResource
    {
        return new ScheduledWebhookResource($scheduledWebhook);
    }

    public function update(UpdateScheduledWebhookRequest $request, ScheduledWebhook $scheduledWebhook): ScheduledWebhookResource
    {
        $scheduledWebhook->update($request->validated());

        return new ScheduledWebhookResource($scheduledWebhook);
    }

    public function destroy(ScheduledWebhook $scheduledWebhook): JsonResponse
    {
        $scheduledWebhook->delete();

        return response()->json(['message' => 'Scheduled webhook deleted.']);
    }
}
