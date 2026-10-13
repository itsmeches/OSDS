<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['code', 'name', 'is_active'])]
class Region extends Model
{
    protected static function booted(): void
    {
        static::creating(fn (Region $region) => $region->public_id ??= (string) Str::uuid());
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function institutions(): HasMany
    {
        return $this->hasMany(Institution::class, 'primary_region_id');
    }

    public function userAssignments(): HasMany
    {
        return $this->hasMany(UserRegionAssignment::class);
    }
}
