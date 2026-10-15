<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class RequirementSetVersion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['version_number' => 'integer', 'configuration_revision' => 'integer', 'lock_version' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn (self $m) => in_array($m->getOriginal('state'), ['published', 'retired'], true) ? throw new LogicException('Published configuration is immutable.') : null);
        static::deleting(fn (self $m) => in_array($m->state, ['published', 'retired'], true) ? throw new LogicException('Published configuration is immutable.') : null);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(OsdsModule::class, 'module_id');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(DocumentaryRequirement::class)->orderBy('sort_order');
    }
}
