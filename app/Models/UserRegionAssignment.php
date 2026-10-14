<?php

namespace App\Models;

use App\Services\Authorization\ScopeAuthorizer;
use App\Services\Identity\AccountGovernance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRegionAssignment extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (UserRegionAssignment $assignment): void {
            if (auth()->check()) {
                $actor = auth()->user();
                $target = User::query()->findOrFail($assignment->user_id);
                if (! app(ScopeAuthorizer::class)->canAccessRegion($actor, $assignment->region_id)
                    || $actor->is($target)
                    || ! app(AccountGovernance::class)->canManageTargetRole($actor, $target)) {
                    throw new AuthorizationException('The selected assignment is outside your delegation authority.');
                }
            }
            $assignment->granted_by ??= auth()->id();
        });
        static::updating(function (UserRegionAssignment $assignment): void {
            if ($assignment->isDirty('revoked_at') && $assignment->revoked_at !== null) {
                $assignment->revoked_by ??= auth()->id();
            }
        });
        static::updated(function (UserRegionAssignment $assignment): void {
            if ($assignment->wasChanged('revoked_at') && $assignment->revoked_at !== null) {
                $assignment->user->invalidateSessions();
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
