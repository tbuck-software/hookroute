<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ConnectionController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        return Inertia::render('Connections/Index', [
            'project' => $project,
            'connections' => $project->connections()->with(['source', 'destination'])->latest()->get(),
            'sources' => $project->sources()->where('enabled', true)->get(['id', 'public_id', 'name']),
            'destinations' => $project->destinations()->where('enabled', true)->get(['id', 'name', 'type']),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        $data = $this->validated($request, $project);
        $project->connections()->create($data);

        return back()->with('success', 'Route created.');
    }

    public function update(Request $request, Project $project, Connection $connection): RedirectResponse
    {
        $this->authorize('update', $project);
        $this->belongsTo($project, $connection);
        $connection->update($this->validated($request, $project));

        return back()->with('success', 'Route updated.');
    }

    public function destroy(Request $request, Project $project, Connection $connection): RedirectResponse
    {
        $this->authorize('update', $project);
        $this->belongsTo($project, $connection);
        $connection->delete();

        return back()->with('success', 'Route deleted.');
    }

    private function validated(Request $request, Project $project): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'source_id' => ['required', Rule::exists('sources', 'id')->where('project_id', $project->id)],
            'destination_id' => ['required', Rule::exists('destinations', 'id')->where('project_id', $project->id)],
            'enabled' => ['sometimes', 'boolean'],
            'payload_mode' => ['required', Rule::in(['passthrough', 'template'])],
            'subject_template' => ['nullable', 'string', 'max:500'],
            'body_template' => ['nullable', 'string', 'max:100_000', 'required_if:payload_mode,template'],
            'filters' => ['nullable', 'array', 'max:10'],
            'filters.*.field' => ['required', 'string', 'max:200'],
            'filters.*.operator' => ['required', Rule::in(['equals', 'not_equals', 'contains', 'exists', 'not_exists', 'greater_than', 'less_than'])],
            'filters.*.value' => ['nullable'],
        ]);
    }

    private function belongsTo(Project $project, Connection $connection): void
    {
        abort_unless($connection->project_id === $project->id, 404);
    }
}
