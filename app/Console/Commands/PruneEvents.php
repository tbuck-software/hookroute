<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;

class PruneEvents extends Command
{
    protected $signature = 'events:prune';

    protected $description = 'Delete captured events beyond each project retention period';

    public function handle(): int
    {
        Project::query()->select(['id', 'event_retention_days'])->chunkById(100, function ($projects) {
            foreach ($projects as $project) {
                $project->events()->where('received_at', '<', now()->subDays($project->event_retention_days))->delete();
            }
        });

        return self::SUCCESS;
    }
}
