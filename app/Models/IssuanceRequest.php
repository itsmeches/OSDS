<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class IssuanceRequest extends Model
{
    protected $guarded = [];

    protected $attributes = ['lock_version' => 1];

    protected function casts(): array
    {
        return ['identity_snapshot' => 'array', 'definition_snapshot' => 'array', 'answers' => 'array', 'submitted_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
    }
}
