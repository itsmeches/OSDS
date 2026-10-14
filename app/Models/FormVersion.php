<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class FormVersion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['version_number' => 'integer', 'configuration_revision' => 'integer', 'lock_version' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $model): void {
            if (in_array($model->getOriginal('state'), ['published', 'retired'], true)) {
                throw new LogicException('Published configuration is immutable.');
            }
        });
        static::deleting(fn (self $model) => in_array($model->state, ['published', 'retired'], true) ? throw new LogicException('Published configuration is immutable.') : null);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class, 'form_definition_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(FormSection::class)->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(FormQuestion::class)->orderBy('sort_order');
    }
}
