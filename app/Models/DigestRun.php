<?php

namespace App\Models;

use Database\Factories\DigestRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigestRun extends Model
{
    /** @use HasFactory<DigestRunFactory> */
    use HasFactory;

    protected $fillable = [
        'destination_id', 'window_start', 'window_end', 'event_ids',
        'event_count', 'total_event_count', 'truncated', 'status', 'processing_started_at',
        'last_error', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'window_start' => 'datetime', 'window_end' => 'datetime',
            'event_ids' => 'array', 'processing_started_at' => 'datetime', 'sent_at' => 'datetime',
            'truncated' => 'boolean',
        ];
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function events()
    {
        return Event::query()->whereKey($this->event_ids ?? [])->orderBy('received_at')->get();
    }
}
