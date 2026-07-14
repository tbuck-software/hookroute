<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Services\RegistrationGate;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request, RegistrationGate $registration): Response
    {
        abort_unless($registration->allows($request), 403, 'Registration is available by invitation only.');

        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, RegistrationGate $registration): RedirectResponse
    {
        abort_unless($registration->allows($request), 403, 'Registration is available by invitation only.');
        $invitation = $registration->invitation($request);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class,
                ...($invitation ? [Rule::in([strtolower($invitation->email)])] : []),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'timezone' => ['nullable', 'timezone'],
        ]);

        $user = DB::transaction(function () use ($request, $invitation) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $base = Str::slug($user->name) ?: 'project';
            $slug = $base;
            while (Project::where('slug', $slug)->exists()) {
                $slug = $base.'-'.Str::lower(Str::random(5));
            }
            $project = Project::create([
                'owner_id' => $user->id,
                'name' => $user->name."'s project",
                'slug' => $slug,
                'timezone' => $request->input('timezone') ?: 'Europe/Berlin',
            ]);
            $project->members()->attach($user, ['role' => 'owner']);

            if ($invitation) {
                $invitation->project->members()->syncWithoutDetaching([
                    $user->id => ['role' => $invitation->role],
                ]);
                $invitation->update(['accepted_at' => now()]);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
