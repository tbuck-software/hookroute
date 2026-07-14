<?php

namespace App\Console\Commands;

use App\Enums\DestinationType;
use App\Jobs\SendDigest;
use App\Models\Destination;
use App\Models\DigestRun;
use App\Models\Event;
use App\Services\DigestWindow;
use App\Services\EventMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class DispatchDigests extends Command
{
    protected $signature = 'digests:dispatch';

    protected $description = 'Create due daily email digest runs';

    public function handle(DigestWindow $windows, EventMatcher $matcher): int
    {
        Destination::query()
            ->where('type', DestinationType::Digest->value)
            ->where('enabled', true)
            ->with('connections')
            ->each(function (Destination $destination) use ($windows, $matcher) {
                $window = $windows->dueWindow($destination->config, now());
                if (! $window) {
                    return;
                }

                [$start, $end] = $window;
                $connectionsBySource = $destination->connections->where('enabled', true)->groupBy('source_id');
                $sourceIds = $connectionsBySource->keys();
                $events = Event::query()
                    ->where('project_id', $destination->project_id)
                    ->whereIn('source_id', $sourceIds)
                    ->where('received_at', '>=', $start)
                    ->where('received_at', '<', $end)
                    ->orderBy('received_at')
                    ->orderBy('id');
                [$eventIds, $totalEventCount] = $this->matchingEvents(
                    $events->cursor(),
                    $connectionsBySource,
                    $matcher,
                );

                $run = DigestRun::firstOrCreate([
                    'destination_id' => $destination->id,
                    'window_start' => $start,
                    'window_end' => $end,
                ], [
                    'event_ids' => $eventIds,
                    'event_count' => $eventIds->count(),
                    'total_event_count' => $totalEventCount,
                    'truncated' => $totalEventCount > $eventIds->count(),
                    'status' => $eventIds->isEmpty() && ! ($destination->config['send_empty'] ?? false) ? 'skipped' : 'pending',
                ]);

                if ($run->wasRecentlyCreated && $run->status === 'pending') {
                    SendDigest::dispatch($run->id);
                }
            });

        return self::SUCCESS;
    }

    private function matchingEvents(iterable $events, Collection $connectionsBySource, EventMatcher $matcher): array
    {
        $ids = collect();
        $total = 0;
        $limit = config('hookroute.digest_max_events');

        foreach ($events as $event) {
            $matches = $connectionsBySource->get($event->source_id, collect())
                ->contains(fn ($connection) => $matcher->matches($event->payload ?? [], $connection->filters));
            if (! $matches) {
                continue;
            }

            $total++;
            if ($ids->count() < $limit) {
                $ids->push($event->id);
            }
        }

        return [$ids, $total];
    }
}
