<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class IssuanceDefinition extends Model
{
    protected $guarded = [];

    protected $attributes = ['lock_version' => 1];

    protected function casts(): array
    {
        return ['definition' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
    }
}
