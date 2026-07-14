<?php

namespace App\Http\Controllers;

use App\Enums\DestinationType;
use App\Models\Destination;
use App\Models\Project;
use App\Services\UrlGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DestinationController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);
        $canManage = $request->user()->can('update', $project);

        return Inertia::render('Destinations/Index', [
            'project' => $project,
            'destinations' => $project->destinations()->withCount('connections')->latest()->get()->map(fn (Destination $destination) => [
                'id' => $destination->id,
                'name' => $destination->name,
                'type' => $destination->type->value,
                'enabled' => $destination->enabled,
                'summary' => $destination->safeSummary($canManage),
                'config' => $canManage ? $this->editableConfig($destination) : null,
                'connections_count' => $destination->connections_count,
                'last_delivered_at' => $destination->last_delivered_at,
            ]),
        ]);
    }

    public function store(Request $request, Project $project, UrlGuard $urlGuard): RedirectResponse
    {
        $this->authorize('update', $project);
        $data = $this->validated($request, $urlGuard);
        $project->destinations()->create($data);

        return back()->with('success', 'Destination created.');
    }

    public function update(Request $request, Project $project, Destination $destination, UrlGuard $urlGuard): RedirectResponse
    {
        $this->authorize('update', $project);
        $this->belongsTo($project, $destination);
        $data = $this->validated($request, $urlGuard);
        if ($data['type'] === 'webhook' && blank($data['config']['signing_secret'] ?? null)) {
            $data['config']['signing_secret'] = $destination->config['signing_secret'] ?? null;
        }
        $destination->update($data);

        return back()->with('success', 'Destination updated.');
    }

    public function destroy(Request $request, Project $project, Destination $destination): RedirectResponse
    {
        $this->authorize('update', $project);
        $this->belongsTo($project, $destination);
        $destination->delete();

        return back()->with('success', 'Destination deleted.');
    }

    private function validated(Request $request, UrlGuard $urlGuard): array
    {
        $base = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(DestinationType::class)],
            'enabled' => ['sometimes', 'boolean'],
            'config' => ['required', 'array'],
        ]);
        $type = DestinationType::from($base['type']);
        $rules = match ($type) {
            DestinationType::Webhook => [
                'config.url' => ['required', 'url', 'starts_with:https://', 'max:2000'],
                'config.method' => ['required', Rule::in(['POST', 'PUT', 'PATCH'])],
                'config.headers' => ['nullable', 'array'],
                'config.headers.*' => ['nullable', 'string', 'max:1000'],
                'config.signing_secret' => ['nullable', 'string', 'min:16', 'max:255'],
            ],
            DestinationType::Discord => [
                'config.url' => ['required', 'url', 'starts_with:https://', 'max:2000'],
                'config.username' => ['nullable', 'string', 'max:80'],
            ],
            DestinationType::Email => [
                'config.recipients' => ['required', 'array', 'min:1', 'max:20'],
                'config.recipients.*' => ['required', 'email', 'max:255'],
            ],
            DestinationType::Digest => [
                'config.recipients' => ['required', 'array', 'min:1', 'max:20'],
                'config.recipients.*' => ['required', 'email', 'max:255'],
                'config.send_time' => ['required', 'date_format:H:i'],
                'config.window_start_time' => ['required', 'date_format:H:i'],
                'config.timezone' => ['required', 'timezone'],
                'config.subject' => ['required', 'string', 'max:180'],
                'config.send_empty' => ['sometimes', 'boolean'],
            ],
        };
        $config = $request->validate($rules)['config'];
        if (in_array($type, [DestinationType::Webhook, DestinationType::Discord], true)) {
            $urlGuard->assertSafe($config['url']);
        }

        return [
            'name' => $base['name'],
            'type' => $type->value,
            'enabled' => $base['enabled'] ?? true,
            'config' => $config,
        ];
    }

    private function editableConfig(Destination $destination): array
    {
        $config = $destination->config;
        if (isset($config['signing_secret'])) {
            $config['signing_secret'] = '';
        }

        return $config;
    }

    private function belongsTo(Project $project, Destination $destination): void
    {
        abort_unless($destination->project_id === $project->id, 404);
    }
}
