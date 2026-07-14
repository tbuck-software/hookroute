<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDelivery;
use App\Models\Delivery;
use App\Models\Event;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);
        $events = $project->events()
            ->with(['source', 'deliveries'])
            ->when($request->string('source')->isNotEmpty(), fn ($query) => $query->where('source_id', $request->integer('source')))
            ->when($request->string('status')->isNotEmpty(), function ($query) use ($request) {
                $status = $request->string('status')->toString();
                $query->whereHas('deliveries', fn ($deliveries) => $deliveries->where('status', $status));
            })
            ->latest('received_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Event $event) => [
                'id' => $event->public_id,
                'source' => ['id' => $event->source->id, 'name' => $event->source->name],
                'received_at' => $event->received_at,
                'content_type' => $event->content_type,
                'delivery_counts' => $event->deliveries->countBy('status'),
                'payload_preview' => mb_substr(json_encode($event->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 0, 240),
            ]);

        return Inertia::render('Events/Index', [
            'project' => $project,
            'events' => $events,
            'sources' => $project->sources()->get(['id', 'name']),
            'filters' => $request->only(['source', 'status']),
        ]);
    }

    public function show(Request $request, Project $project, Event $event): Response
    {
        $this->authorize('view', $project);
        $this->belongsTo($project, $event);
        $event->load(['source', 'deliveries.destination', 'deliveries.connection']);
        if (! $request->user()->can('update', $project)) {
            $event->deliveries->each->makeHidden('response_excerpt');
        }

        return Inertia::render('Events/Show', [
            'project' => $project,
            'event' => $event,
        ]);
    }

    public function replay(Request $request, Project $project, Event $event, Delivery $delivery): RedirectResponse
    {
        $this->authorize('update', $project);
        $this->belongsTo($project, $event);
        abort_unless($delivery->event_id === $event->id, 404);
        $queued = Delivery::query()
            ->whereKey($delivery->id)
            ->whereNotIn('status', ['pending', 'processing', 'retrying'])
            ->update([
                'status' => 'pending', 'attempts' => 0, 'response_status' => null,
                'response_excerpt' => null, 'last_error' => null, 'delivered_at' => null,
            ]);
        if ($queued) {
            ProcessDelivery::dispatch($delivery->id);
        }

        return back()->with('success', $queued ? 'Delivery queued for replay.' : 'Delivery is already queued or processing.');
    }

    private function belongsTo(Project $project, Event $event): void
    {
        abort_unless($event->project_id === $project->id, 404);
    }
}
