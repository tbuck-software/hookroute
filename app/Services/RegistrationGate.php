<?php

namespace App\Services;

use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Http\Request;

class RegistrationGate
{
    public function allows(Request $request): bool
    {
        return config('hookroute.allow_public_registration')
            || User::query()->doesntExist()
            || $this->invitation($request) !== null;
    }

    public function invitation(Request $request): ?ProjectInvitation
    {
        $intended = $request->session()->get('url.intended');
        if (! is_string($intended)) {
            return null;
        }

        $path = parse_url($intended, PHP_URL_PATH);
        if (! is_string($path) || ! preg_match('#/invitations/([^/]+)$#', $path, $matches)) {
            return null;
        }

        return ProjectInvitation::query()
            ->where('token', $matches[1])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
    }
}
