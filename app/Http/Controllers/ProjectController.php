<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Projects/Index', [
            'projects' => $request->user()->projects()->withCount(['sources', 'destinations', 'events'])->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'timezone' => ['required', 'timezone'],
        ]);

        $project = DB::transaction(function () use ($request, $data) {
            $base = Str::slug($data['name']) ?: 'project';
            $slug = $base;
            while (Project::where('slug', $slug)->exists()) {
                $slug = $base.'-'.Str::lower(Str::random(5));
            }
            $project = Project::create([
                ...$data,
                'slug' => $slug,
                'owner_id' => $request->user()->id,
            ]);
            $project->members()->attach($request->user(), ['role' => 'owner']);

            return $project;
        });

        return redirect()->route('projects.dashboard', $project)->with('success', 'Project created.');
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        $project->update($request->validate([
            'name' => ['required', 'string', 'max:100'],
            'timezone' => ['required', 'timezone'],
            'event_retention_days' => ['required', 'integer', Rule::in([1, 7, 14, 30, 60, 90])],
        ]));

        return back()->with('success', 'Project settings saved.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);
        DB::transaction(function () use ($request, $project) {
            $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->id);
            abort_unless($lockedProject->owner_id === $request->user()->id, 403);
            $lockedProject->delete();
        });

        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }
}
