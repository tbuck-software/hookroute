<?php

namespace App\Models;

use App\Enums\DestinationType;
use Database\Factories\DestinationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Destination extends Model
{
    /** @use HasFactory<DestinationFactory> */
    use HasFactory;

    protected $fillable = ['project_id', 'name', 'type', 'config', 'enabled', 'last_delivered_at'];

    protected $hidden = ['config'];

    protected function casts(): array
    {
        return [
            'type' => DestinationType::class,
            'config' => 'encrypted:array',
            'enabled' => 'boolean',
            'last_delivered_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function connections(): HasMany
    {
        return $this->hasMany(Connection::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function digestRuns(): HasMany
    {
        return $this->hasMany(DigestRun::class);
    }

    public function safeSummary(bool $includeRecipients = true): string
    {
        $config = $this->config;

        return match ($this->type) {
            DestinationType::Webhook => parse_url($config['url'] ?? '', PHP_URL_HOST) ?: 'HTTP endpoint',
            DestinationType::Discord => 'Discord webhook',
            DestinationType::Email, DestinationType::Digest => $includeRecipients
                ? implode(', ', $config['recipients'] ?? [])
                : count($config['recipients'] ?? []).' '.(count($config['recipients'] ?? []) === 1 ? 'recipient' : 'recipients'),
        };
    }
}
