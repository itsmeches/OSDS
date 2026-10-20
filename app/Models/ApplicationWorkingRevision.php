<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApplicationWorkingRevision extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime', 'consumed_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(ApplicationDraft::class, 'application_draft_id');
    }
}
