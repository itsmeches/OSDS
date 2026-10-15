<?php

namespace App\Models;

use App\Services\Applicants\ApplicantSandboxGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantSandboxEligibilityGrant extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (self $grant): void {
            $institution = Institution::findOrFail($grant->institution_id);
            $actor = User::find($grant->granted_by);
            if (! app(ApplicantSandboxGuard::class)->allowed() || ! $institution->is_synthetic || ! $actor?->isActive() || ! $actor->can('applicant.sandbox.publish')) {
                throw new AuthorizationException;
            }
        });
    }

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(OsdsModule::class);
    }
}
