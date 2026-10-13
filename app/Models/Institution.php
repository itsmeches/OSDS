<?php

namespace App\Models;

use App\Services\Authorization\ScopeAuthorizer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'official_id', 'primary_region_id', 'status'])]
class Institution extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Institution $institution): void {
            $institution->public_id ??= (string) Str::uuid();
            if (auth()->check() && $institution->primary_region_id !== null
                && ! app(ScopeAuthorizer::class)->canAccessRegion(auth()->user(), $institution->primary_region_id)) {
                throw new AuthorizationException('The selected region is outside your authorization scope.');
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
}
