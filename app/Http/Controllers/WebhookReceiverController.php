<?php

namespace App\Http\Controllers;

use App\Models\Source;
use App\Services\EventIngestor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookReceiverController extends Controller
{
    public function __invoke(Request $request, Source $source, string $secret, EventIngestor $ingestor): JsonResponse
    {
        abort_unless($source->enabled && $source->acceptsSecret($secret), 404);

        if ($source->signing_secret && $source->signature_header) {
            $provided = (string) $request->header($source->signature_header);
            $provided = str_starts_with($provided, 'sha256=') ? substr($provided, 7) : $provided;
            $expected = hash_hmac('sha256', $request->getContent(), $source->signing_secret);
            abort_unless($provided !== '' && hash_equals($expected, $provided), 401, 'Invalid webhook signature.');
        }

        [$event, $duplicate] = $ingestor->ingest($source, $request);

        return response()->json([
            'event_id' => $event->public_id,
            'duplicate' => $duplicate,
        ], $duplicate ? 200 : 202);
    }
}
