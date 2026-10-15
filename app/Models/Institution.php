<?php

namespace App\Models;

use App\Services\Authorization\ScopeAuthorizer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'display_name', 'official_id', 'primary_region_id', 'status', 'organization_type', 'is_synthetic'])]
class Institution extends Model
{
    protected $attributes = ['status' => 'active', 'organization_type' => 'hei', 'is_synthetic' => false];

    protected static function booted(): void
    {
        static::creating(function (Institution $institution): void {
            $institution->public_id ??= (string) Str::uuid();
            if (auth()->check() && $institution->primary_region_id !== null
                && ! app(ScopeAuthorizer::class)->canAccessRegion(auth()->user(), $institution->primary_region_id)) {
                throw new AuthorizationException('The selected region is outside your authorization scope.');
            }
        });
        static::updating(function (Institution $institution): void {
            if ($institution->isDirty('organization_type') && ApplicationDraft::query()->where('institution_id', $institution->id)->exists()) {
                throw new \LogicException('Organization type cannot change after an applicant draft exists.');
            }
        });
    }

    public function primaryRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'primary_region_id');
    }

    public function representatives(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'institution_representatives')
            ->withPivot(['starts_at', 'ends_at', 'revoked_at'])->withTimestamps();
    }

    protected function casts(): array
    {
        return ['is_synthetic' => 'boolean'];
    }

    public function isHei(): bool
    {
        return $this->organization_type === 'hei';
    }

    public function mayUseApplicantSandbox(): bool
    {
        return $this->is_synthetic && $this->status === 'active';
    }
}
