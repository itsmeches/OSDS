<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScopedAuthorizationGrant extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(fn (ScopedAuthorizationGrant $grant) => $grant->granted_by ??= auth()->id());
        static::updating(function (ScopedAuthorizationGrant $grant): void {
            if ($grant->isDirty('revoked_at') && $grant->revoked_at !== null) {
                $grant->revoked_by ??= auth()->id();
            }
        });
    }

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
