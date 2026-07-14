<?php

namespace App\Models;

use Database\Factories\SourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    /** @use HasFactory<SourceFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id', 'public_id', 'name', 'slug', 'secret', 'secret_hash',
        'signing_secret', 'signature_header', 'enabled', 'last_received_at',
    ];

    protected $hidden = ['secret', 'secret_hash', 'signing_secret'];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'signing_secret' => 'encrypted',
            'enabled' => 'boolean',
            'last_received_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function connections(): HasMany
    {
        return $this->hasMany(Connection::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function webhookPath(): string
    {
        return '/hooks/'.$this->public_id.'/'.$this->secret;
    }

    public function webhookUrl(): string
    {
        return url($this->webhookPath());
    }

    public function acceptsSecret(string $secret): bool
    {
        return hash_equals($this->secret_hash, hash('sha256', $secret));
    }
}
