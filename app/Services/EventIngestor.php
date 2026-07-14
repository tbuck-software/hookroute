<?php

namespace App\Services;

use App\Enums\DestinationType;
use App\Jobs\ProcessDelivery;
use App\Models\Event;
use App\Models\Source;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EventIngestor
{
    public function __construct(
        private readonly EventMatcher $matcher,
        private readonly HeaderRedactor $redactor,
    ) {}

    /** @return array{Event, bool} */
    public function ingest(Source $source, Request $request): array
    {
        $rawBody = $request->getContent();
        if (strlen($rawBody) > config('hookroute.max_payload_bytes')) {
            throw new HttpException(413, 'Webhook payload is too large.');
        }

        $idempotencyKey = $request->header('Idempotency-Key');
        if (is_string($idempotencyKey) && mb_strlen($idempotencyKey) > 255) {
            throw new HttpException(400, 'Idempotency-Key must not exceed 255 characters.');
        }
        if ($idempotencyKey) {
            $existing = $source->events()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return [$existing, true];
            }
        }

        $payload = $this->payload($request, $rawBody);

        try {
            return DB::transaction(function () use ($source, $request, $rawBody, $payload, $idempotencyKey) {
                $event = Event::create([
                    'project_id' => $source->project_id,
                    'source_id' => $source->id,
                    'public_id' => (string) Str::ulid(),
                    'method' => $request->method(),
                    'content_type' => $request->header('Content-Type'),
                    'headers' => $this->redactor->redact(
                        $request->headers->all(),
                        array_filter([$source->signature_header]),
                    ),
                    'raw_body' => $rawBody,
                    'payload' => $payload,
                    'idempotency_key' => $idempotencyKey,
                    'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), config('app.key')) : null,
                    'received_at' => now(),
                ]);

                $connections = $source->connections()
                    ->where('enabled', true)
                    ->with('destination')
                    ->get();

                foreach ($connections as $connection) {
                    $destination = $connection->destination;
                    if (! $destination->enabled || $destination->type === DestinationType::Digest) {
                        continue;
                    }
                    if (! $this->matcher->matches($payload ?? [], $connection->filters)) {
                        continue;
                    }

                    $delivery = $event->deliveries()->create([
                        'connection_id' => $connection->id,
                        'destination_id' => $destination->id,
                        'status' => 'pending',
                    ]);
                    ProcessDelivery::dispatch($delivery->id)->afterCommit();
                }

                $source->update(['last_received_at' => now()]);

                return [$event, false];
            });
        } catch (QueryException $exception) {
            if ($idempotencyKey && $existing = $source->events()->where('idempotency_key', $idempotencyKey)->first()) {
                return [$existing, true];
            }

            throw $exception;
        }
    }

    private function payload(Request $request, string $rawBody): ?array
    {
        if ($request->isJson()) {
            $decoded = json_decode($rawBody, true);

            return is_array($decoded) ? $decoded : null;
        }

        $data = $request->all();

        return $data !== [] ? $data : null;
    }
}
