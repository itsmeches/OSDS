<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use LogicException;

class PublicationManifest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['effective_from' => 'immutable_datetime', 'effective_until' => 'immutable_datetime', 'published_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
        static::updating(fn () => throw new LogicException('Publication manifests are append-only.'));
        static::deleting(fn () => throw new LogicException('Publication manifests are append-only.'));
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

    public function approval(): BelongsTo
    {
        return $this->belongsTo(ConfigurationReviewApproval::class, 'configuration_review_approval_id');
    }

    public function retirement(): HasOne
    {
        return $this->hasOne(PublicationManifestRetirement::class);
    }
}
