<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $project = $request->route('project');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'projects' => fn () => $request->user()?->projects()->orderBy('name')->get(['projects.id', 'name', 'slug']) ?? [],
            'currentProject' => $project instanceof Project ? [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'timezone' => $project->timezone,
                'can_manage' => $request->user()?->can('update', $project) ?? false,
                'is_owner' => $request->user()?->can('delete', $project) ?? false,
            ] : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'created_source_url' => fn () => $request->session()->get('created_source_url'),
            ],
        ];
    }
}
