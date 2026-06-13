<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreScheduledWebhookRequest;
use App\Http\Requests\Admin\UpdateScheduledWebhookRequest;
use App\Http\Resources\ScheduledWebhookResource;
use App\Models\ScheduledWebhook;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ScheduledWebhookController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Scheduler/Index', [
            'webhooks' => ScheduledWebhookResource::collection(
                ScheduledWebhook::orderBy('name')->paginate(25)
            ),
        ]);
    }

    public function store(StoreScheduledWebhookRequest $request): RedirectResponse
    {
        ScheduledWebhook::create($request->validated());

        return back()->with('success', 'Webhook created.');
    }

    public function update(UpdateScheduledWebhookRequest $request, ScheduledWebhook $scheduledWebhook): RedirectResponse
    {
        $scheduledWebhook->update($request->validated());

        return back()->with('success', 'Webhook updated.');
    }

    public function destroy(ScheduledWebhook $scheduledWebhook): RedirectResponse
    {
        $scheduledWebhook->delete();

        return back()->with('success', 'Webhook deleted.');
    }
}
