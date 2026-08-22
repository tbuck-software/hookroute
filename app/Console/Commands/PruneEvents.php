<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Project;
use Illuminate\Console\Command;

class PruneEvents extends Command
{
    protected $signature = 'events:prune';

    protected $description = 'Delete captured events beyond each project retention period';

    public function handle(): int
    {
        $batchSize = max(1, (int) config('hookroute.prune_batch_size', 500));

        Project::query()->select(['id', 'event_retention_days'])->chunkById(100, function ($projects) use ($batchSize) {
            foreach ($projects as $project) {
                $cutoff = now()->subDays($project->event_retention_days);
                $this->deleteInBatches($project->events(), $cutoff, $batchSize);
            }
        });

        return self::SUCCESS;
    }

    private function deleteInBatches($events, $cutoff, int $batchSize): void
    {
        do {
            $ids = $events
                ->where('received_at', '<', $cutoff)
                ->limit($batchSize)
                ->pluck('id');
            if ($ids->isNotEmpty()) {
                Event::whereIn('id', $ids)->delete();
            }
        } while ($ids->count() === $batchSize);
    }
}
