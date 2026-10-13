<?php

namespace App\Models;

use App\Services\Authorization\ScopeAuthorizer;
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
            if (auth()->check() && ! app(ScopeAuthorizer::class)->canAccessInstitution(auth()->user(), $institution)) {
                throw new AuthorizationException('The selected institution is outside your authorization scope.');
            }
            $grant->granted_by ??= auth()->id();
        });
        static::updating(function (InstitutionRepresentative $grant): void {
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

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
