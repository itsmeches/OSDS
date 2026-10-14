<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OsdsModule extends Model
{
    protected $table = 'modules';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->public_id ??= (string) Str::uuid());
    }

    public function formDefinitions(): HasMany
    {
        return $this->hasMany(FormDefinition::class, 'module_id');
    }

    public function requirementSetVersions(): HasMany
    {
        return $this->hasMany(RequirementSetVersion::class, 'module_id');
    }
}
