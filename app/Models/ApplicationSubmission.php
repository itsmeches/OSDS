<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class ApplicationSubmission extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['submitted_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
        static::updating(fn () => throw new LogicException('Submissions are immutable.'));
        static::deleting(fn () => throw new LogicException('Submissions are immutable.'));
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ApplicationSubmissionAnswer::class, 'submission_id');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ApplicationSubmissionRequirement::class, 'submission_id');
    }

    public function workingRevision(): BelongsTo
    {
        return $this->belongsTo(ApplicationWorkingRevision::class, 'working_revision_id');
    }
}
