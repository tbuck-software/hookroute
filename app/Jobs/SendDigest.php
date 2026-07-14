<?php

namespace App\Jobs;

use App\Mail\DigestMail;
use App\Models\DigestRun;
use App\Models\Event;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendDigest implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly int $digestRunId) {}

    public function backoff(): array
    {
        return [300, 1800];
    }

    public function handle(): void
    {
        $claimed = DigestRun::query()
            ->whereKey($this->digestRunId)
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'retrying'])
                    ->orWhere(function ($stale) {
                        $stale->where('status', 'processing')
                            ->where(function ($lease) {
                                $lease->whereNull('processing_started_at')
                                    ->orWhere('processing_started_at', '<=', now()->subSeconds(config('hookroute.digest_processing_lease_seconds')));
                            });
                    });
            })
            ->update(['status' => 'processing', 'processing_started_at' => now(), 'last_error' => null]);
        if (! $claimed) {
            return;
        }

        $run = DigestRun::with('destination.project')->find($this->digestRunId);
        if (! $run) {
            return;
        }

        try {
            $config = $run->destination->config;
            $eventIds = Event::query()
                ->whereKey($run->event_ids ?? [])
                ->orderBy('received_at')
                ->pluck('id');
            $run->update(['event_ids' => $eventIds, 'event_count' => $eventIds->count()]);
            if (! $run->destination->enabled || ($eventIds->isEmpty() && ! ($config['send_empty'] ?? false))) {
                $run->update(['status' => 'skipped', 'processing_started_at' => null]);

                return;
            }

            Mail::to($config['recipients'] ?? [])->send(new DigestMail($run));
            $run->update([
                'status' => 'sent', 'processing_started_at' => null,
                'sent_at' => now(), 'last_error' => null,
            ]);
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'retrying', 'processing_started_at' => null,
                'last_error' => 'Digest email delivery failed.',
            ]);

            throw new \RuntimeException('Digest email delivery failed.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        DigestRun::whereKey($this->digestRunId)->update([
            'status' => 'failed',
            'processing_started_at' => null,
            'last_error' => 'Digest email delivery failed.',
        ]);
    }
}
