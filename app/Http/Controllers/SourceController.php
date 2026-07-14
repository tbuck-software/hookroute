<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Source;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SourceController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);
        $canManage = $request->user()->can('update', $project);

        return Inertia::render('Sources/Index', [
            'project' => $project,
            'sources' => $project->sources()->withCount(['connections', 'events'])->latest()->get()->map(fn (Source $source) => [
                'id' => $source->public_id,
                'name' => $source->name,
                'slug' => $source->slug,
                'enabled' => $source->enabled,
                'webhook_url' => $canManage ? $source->webhookUrl() : null,
                'signature_header' => $source->signature_header,
                'has_signing_secret' => filled($source->signing_secret),
                'connections_count' => $source->connections_count,
                'events_count' => $source->events_count,
                'last_received_at' => $source->last_received_at,
            ]),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'signing_secret' => ['nullable', 'string', 'min:16', 'max:255'],
            'signature_header' => ['nullable', 'required_with:signing_secret', 'string', 'max:100'],
        ]);
        $secret = Str::random(48);
        $base = Str::slug($data['name']) ?: 'source';
        $slug = $base;
        $suffix = 2;
        while ($project->sources()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }
        $source = $project->sources()->create([
            ...$data,
            'public_id' => (string) Str::ulid(),
            'slug' => $slug,
            'secret' => $secret,
            'secret_hash' => hash('sha256', $secret),
            'enabled' => true,
        ]);

        return back()->with('success', 'Source created.')->with('created_source_url', $source->webhookUrl());
    }

    public function update(Request $request, Project $project, Source $source): RedirectResponse
    {
        $this->authorize('update', $project);
        $this->belongsTo($project, $source);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'enabled' => ['required', 'boolean'],
            'signing_secret' => ['nullable', 'string', 'min:16', 'max:255'],
            'signature_header' => ['nullable', 'required_with:signing_secret', 'string', 'max:100'],
            'clear_signing_secret' => ['sometimes', 'boolean'],
        ]);
        if ($data['clear_signing_secret'] ?? false) {
            $data['signing_secret'] = null;
            $data['signature_header'] = null;
        } elseif (blank($data['signing_secret'] ?? null)) {
            unset($data['signing_secret']);
        }
        unset($data['clear_signing_secret']);
        $source->update($data);

        return back()->with('success', 'Source updated.');
    }

    public function rotate(Request $request, Project $project, Source $source): RedirectResponse
    {
        $this->authorize('update', $project);
        $this->belongsTo($project, $source);
        $secret = Str::random(48);
        $source->update(['secret' => $secret, 'secret_hash' => hash('sha256', $secret)]);

        return back()->with('success', 'Source URL rotated.')->with('created_source_url', $source->fresh()->webhookUrl());
    }

    public function destroy(Request $request, Project $project, Source $source): RedirectResponse
    {
        $this->authorize('update', $project);
        $this->belongsTo($project, $source);
        $source->delete();

        return back()->with('success', 'Source deleted.');
    }

    private function belongsTo(Project $project, Source $source): void
    {
        abort_unless($source->project_id === $project->id, 404);
    }
}
