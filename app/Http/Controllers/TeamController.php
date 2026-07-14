<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Notifications\ProjectInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);
        $canManage = $request->user()->can('manageTeam', $project);

        return Inertia::render('Team/Index', [
            'project' => $project,
            'members' => $project->members()->get()->map(fn (User $user) => [
                'id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->pivot->role,
            ]),
            'invitations' => $canManage
                ? $project->invitations()->whereNull('accepted_at')->where('expires_at', '>', now())
                    ->get(['id', 'email', 'role', 'expires_at'])
                : [],
        ]);
    }

    public function invite(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('manageTeam', $project);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in([ProjectRole::Admin->value, ProjectRole::Member->value])],
        ]);
        if ($project->members()->where('email', $data['email'])->exists()) {
            return back()->withErrors(['email' => 'This user is already a member.']);
        }

        $invitation = ProjectInvitation::updateOrCreate(
            ['project_id' => $project->id, 'email' => strtolower($data['email'])],
            [
                'invited_by' => $request->user()->id,
                'role' => $data['role'],
                'token' => Str::random(64),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
            ],
        );
        $invitation->load(['project', 'inviter']);
        $existing = User::where('email', $invitation->email)->first();
        $existing
            ? $existing->notify(new ProjectInvitationNotification($invitation))
            : Notification::route('mail', $invitation->email)->notify(new ProjectInvitationNotification($invitation));

        return back()->with('success', 'Invitation sent.');
    }

    public function updateRole(Request $request, Project $project, User $user): RedirectResponse
    {
        $this->authorize('manageTeam', $project);
        $data = $request->validate(['role' => ['required', Rule::in(['admin', 'member'])]]);
        DB::transaction(function () use ($project, $user, $data) {
            $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->id);
            abort_unless($lockedProject->members()->whereKey($user->id)->exists(), 404);
            abort_if($lockedProject->owner_id === $user->id, 422, 'The owner role cannot be changed.');
            $lockedProject->members()->updateExistingPivot($user, ['role' => $data['role']]);
        });

        return back()->with('success', 'Member role updated.');
    }

    public function remove(Request $request, Project $project, User $user): RedirectResponse
    {
        $this->authorize('manageTeam', $project);
        DB::transaction(function () use ($project, $user) {
            $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->id);
            abort_if($lockedProject->owner_id === $user->id, 422, 'The owner cannot be removed.');
            $lockedProject->members()->detach($user);
        });

        return back()->with('success', 'Member removed.');
    }

    public function transferOwnership(Request $request, Project $project, User $user): RedirectResponse
    {
        $this->authorize('transferOwnership', $project);

        DB::transaction(function () use ($request, $project, $user) {
            $lockedTarget = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->id);
            abort_unless($lockedProject->owner_id === $request->user()->id, 403);
            abort_unless($lockedProject->members()->whereKey($lockedTarget->id)->exists(), 404);
            abort_if($lockedProject->owner_id === $lockedTarget->id, 422, 'This user already owns the project.');

            DB::table('project_user')
                ->where('project_id', $lockedProject->id)
                ->where('role', ProjectRole::Owner->value)
                ->update(['role' => ProjectRole::Admin->value, 'updated_at' => now()]);
            DB::table('project_user')
                ->where('project_id', $lockedProject->id)
                ->where('user_id', $lockedTarget->id)
                ->update(['role' => ProjectRole::Owner->value, 'updated_at' => now()]);
            $lockedProject->update(['owner_id' => $lockedTarget->id]);
        });

        return back()->with('success', 'Project ownership transferred.');
    }
}
