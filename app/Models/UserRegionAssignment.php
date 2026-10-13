<?php

namespace App\Models;

use App\Services\Authorization\ScopeAuthorizer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRegionAssignment extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (UserRegionAssignment $assignment): void {
            if (auth()->check() && ! app(ScopeAuthorizer::class)->canAccessRegion(auth()->user(), $assignment->region_id)) {
                throw new AuthorizationException('The selected region is outside your authorization scope.');
            }
            $assignment->granted_by ??= auth()->id();
        });
        static::updating(function (UserRegionAssignment $assignment): void {
            if ($assignment->isDirty('revoked_at') && $assignment->revoked_at !== null) {
                $assignment->revoked_by ??= auth()->id();
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
}
