<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $project = $request->user()->projects()->first();

        return $project
            ? redirect()->route('projects.dashboard', $project)
            : redirect()->route('projects.index');
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);
        $deliveryQuery = Delivery::whereHas('event', fn ($query) => $query->where('project_id', $project->id));
        $recentEvents = $project->events()->with(['source', 'deliveries.destination'])->latest('received_at')->limit(8)->get();

        return Inertia::render('Dashboard', [
            'project' => $project,
            'metrics' => [
                'events_24h' => $project->events()->where('received_at', '>=', now()->subDay())->count(),
                'active_sources' => $project->sources()->where('enabled', true)->count(),
                'active_routes' => $project->connections()->where('enabled', true)->count(),
                'failed_deliveries' => (clone $deliveryQuery)->where('status', 'failed')->count(),
                'delivery_rate' => $this->deliveryRate(clone $deliveryQuery),
            ],
            'recentEvents' => $recentEvents->map(fn ($event) => [
                'id' => $event->public_id,
                'source' => $event->source->name,
                'received_at' => $event->received_at,
                'deliveries' => $event->deliveries->map(fn ($delivery) => [
                    'name' => $delivery->destination->name,
                    'status' => $delivery->status,
                ]),
                'payload_preview' => json_encode($event->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]),
        ]);
    }

    private function deliveryRate($query): int
    {
        $query->where('deliveries.created_at', '>=', now()->subDay());
        $total = (clone $query)->count();

        return $total === 0 ? 100 : (int) round((clone $query)->where('status', 'delivered')->count() / $total * 100);
    }
}
