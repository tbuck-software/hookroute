<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Reject nested resources that do not belong to the routed project,
     * even when the route binding itself resolves.
     */
    protected function belongsToProject(Model $child, Project $project): void
    {
        abort_unless($child->project_id === $project->id, 404);
    }
}
