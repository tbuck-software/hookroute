<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id', 'source_id', 'public_id', 'method', 'content_type', 'headers',
        'raw_body', 'payload', 'idempotency_key', 'ip_hash', 'received_at',
    ];

    protected function casts(): array
    {
        return ['headers' => 'array', 'payload' => 'array', 'received_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function templateContext(): array
    {
        return [
            'event' => ['id' => $this->public_id, 'received_at' => $this->received_at?->toIso8601String()],
            'payload' => $this->payload ?? [],
            'source' => ['id' => $this->source->public_id, 'name' => $this->source->name],
            'project' => ['name' => $this->project->name, 'slug' => $this->project->slug],
        ];
    }
}
