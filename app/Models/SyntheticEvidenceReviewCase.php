<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class SyntheticEvidenceReviewCase extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
        static::updating(fn () => throw new LogicException('Review copies are immutable.'));
        static::deleting(fn () => throw new LogicException('Review copies are immutable.'));
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(SyntheticEvidenceReviewRequirement::class, 'case_id')->orderBy('sort_order');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(OsdsModule::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function newerCopies(): HasMany
    {
        return $this->hasMany(self::class, 'supersedes_case_id');
    }
}
