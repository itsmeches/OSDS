<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use LogicException;

class EvidenceReference extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->public_id ??= (string) Str::uuid());
        static::updating(fn () => throw new LogicException('Evidence reference bindings are immutable.'));
        static::deleting(fn () => throw new LogicException('Evidence references preserve history.'));
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(ApplicationDraft::class, 'application_draft_id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(DocumentaryRequirement::class, 'documentary_requirement_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(EvidenceReferenceRevision::class);
    }

    public function latestRevision(): HasOne
    {
        return $this->hasOne(EvidenceReferenceRevision::class)->ofMany('revision_number', 'max');
    }
}
