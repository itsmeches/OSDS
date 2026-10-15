<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ConfigurationReviewRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['submitted_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(OsdsModule::class, 'module_id');
    }

    public function formVersion(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class);
    }

    public function requirementSetVersion(): BelongsTo
    {
        return $this->belongsTo(RequirementSetVersion::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ConfigurationReviewApproval::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
