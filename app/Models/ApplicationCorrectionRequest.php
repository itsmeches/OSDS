<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ApplicationCorrectionRequest extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['requested_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
    }

    public function items(): HasMany
    {
        return $this->hasMany(ApplicationCorrectionItem::class, 'correction_request_id');
    }
}
