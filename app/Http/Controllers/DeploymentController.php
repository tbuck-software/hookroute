<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DeploymentController extends Controller
{
    private const SIGNATURE_TTL_SECONDS = 300;

    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('hookroute.deploy_secret');

        abort_unless(is_string($secret) && $secret !== '', 404);

        $timestamp = $request->header('X-Hookroute-Deploy-Timestamp');
        $signature = $request->header('X-Hookroute-Deploy-Signature');

        abort_unless(
            is_string($timestamp)
                && ctype_digit($timestamp)
                && abs(time() - (int) $timestamp) <= self::SIGNATURE_TTL_SECONDS
                && is_string($signature),
            401,
        );

        $expectedSignature = hash_hmac(
            'sha256',
            $timestamp."\n".$request->getContent(),
            $secret,
        );

        abort_unless(hash_equals($expectedSignature, $signature), 401);

        $lock = fopen(storage_path('framework/deployment.lock'), 'c');

        abort_if($lock === false, 500, 'Unable to create deployment lock.');

        try {
            abort_unless(flock($lock, LOCK_EX | LOCK_NB), 409, 'A deployment is already running.');

            @chmod(base_path('.env'), 0600);
            Artisan::call('migrate', ['--force' => true]);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        return response()->json(['deployed' => true]);
    }
}
