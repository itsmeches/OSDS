<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;

class SyntheticEvidenceReviewFinding extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['reviewed_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
        static::updating(fn () => throw new LogicException('Findings are append-only.'));
        static::deleting(fn () => throw new LogicException('Findings are append-only.'));
    }
}
