<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class FormDefinition extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->public_id ??= (string) Str::uuid());
        $guard = fn (self $model) => $model->versions()->whereIn('state', ['published', 'retired'])->exists() ? throw new LogicException('Published configuration is immutable.') : null;
        static::updating($guard);
        static::deleting($guard);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(OsdsModule::class, 'module_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FormVersion::class);
    }
}
