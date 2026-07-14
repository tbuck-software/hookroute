<?php

namespace App\Http\Controllers;

use App\Models\ProjectInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(Request $request, string $token): Response|RedirectResponse
    {
        $invitation = ProjectInvitation::with(['project', 'inviter'])->where('token', $token)->firstOrFail();
        abort_if($invitation->accepted_at || $invitation->expires_at->isPast(), 410, 'This invitation is no longer valid.');

        if (! $request->user()) {
            $request->session()->put('url.intended', route('invitations.show', $token));

            return redirect()->route('login')->with('status', 'Sign in or create an account to accept the invitation.');
        }

        return Inertia::render('Invitations/Show', [
            'invitation' => [
                'token' => $invitation->token,
                'project' => $invitation->project->name,
                'inviter' => $invitation->inviter->name,
                'role' => $invitation->role,
                'email' => $invitation->email,
                'expires_at' => $invitation->expires_at,
            ],
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = ProjectInvitation::with('project')->where('token', $token)->firstOrFail();
        abort_if($invitation->accepted_at || $invitation->expires_at->isPast(), 410, 'This invitation is no longer valid.');
        abort_unless(strtolower($request->user()->email) === strtolower($invitation->email), 403);

        $invitation->project->members()->syncWithoutDetaching([
            $request->user()->id => ['role' => $invitation->role],
        ]);
        $invitation->update(['accepted_at' => now()]);

        return redirect()->route('projects.dashboard', $invitation->project)->with('success', 'Invitation accepted.');
    }
}
