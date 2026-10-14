<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class FormQuestion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'validation_rules' => 'array', 'condition_rules' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $m): void {
            $m->form_version_id ??= $m->section()->value('form_version_id');
        });
        $guard = fn (self $m) => in_array($m->version->state, ['published', 'retired'], true) ? throw new LogicException('Published configuration is immutable.') : null;
        static::updating($guard);
        static::deleting($guard);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'form_version_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(FormSection::class, 'form_section_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(FormQuestionOption::class)->orderBy('sort_order');
    }
}
