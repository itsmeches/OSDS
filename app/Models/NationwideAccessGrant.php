<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NationwideAccessGrant extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::updated(function (NationwideAccessGrant $grant): void {
            if ($grant->wasChanged('revoked_at') && $grant->revoked_at !== null) {
                $grant->user->invalidateSessions();
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
}
