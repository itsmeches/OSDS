<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class ConfigurationReviewApproval extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['reviewed_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
        static::updating(fn () => throw new LogicException('Review approvals are append-only.'));
        static::deleting(fn () => throw new LogicException('Review approvals are append-only.'));
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ConfigurationReviewRequest::class, 'configuration_review_request_id');
    }
}
