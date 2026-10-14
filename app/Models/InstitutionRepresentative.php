<?php

namespace App\Models;

use App\Services\Authorization\ScopeAuthorizer;
use App\Services\Identity\AccountGovernance;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionRepresentative extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (InstitutionRepresentative $grant): void {
            $institution = Institution::query()->findOrFail($grant->institution_id);
            if (auth()->check()) {
                $actor = auth()->user();
                $target = User::query()->findOrFail($grant->user_id);
                if (! app(ScopeAuthorizer::class)->canAccessInstitution($actor, $institution)
                    || $actor->is($target)
                    || ! app(AccountGovernance::class)->canManageTargetRole($actor, $target)) {
                    throw new AuthorizationException('The selected representation is outside your delegation authority.');
                }
            }
            $grant->granted_by ??= auth()->id();
        });
        static::updated(function (InstitutionRepresentative $grant): void {
            if ($grant->wasChanged('revoked_at') && $grant->revoked_at !== null) {
                $grant->user->invalidateSessions();
            }
        });
        static::updating(function (InstitutionRepresentative $grant): void {
            if ($grant->isDirty('revoked_at') && $grant->revoked_at !== null) {
                $grant->revoked_by ??= auth()->id();
            }
        });
    }

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime', 'verified_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
