<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class PublicationManifestRetirement extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['retired_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Manifest retirements are append-only.'));
        static::deleting(fn () => throw new LogicException('Manifest retirements are append-only.'));
    }
}
