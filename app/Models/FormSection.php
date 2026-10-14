<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class FormSection extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        $guard = fn (self $m) => in_array($m->version->state, ['published', 'retired'], true) ? throw new LogicException('Published configuration is immutable.') : null;
        static::updating($guard);
        static::deleting($guard);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'form_version_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(FormQuestion::class)->orderBy('sort_order');
    }
}
