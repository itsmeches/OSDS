<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class FormQuestionOption extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        $guard = fn (self $m) => in_array($m->question->version->state, ['published', 'retired'], true) ? throw new LogicException('Published configuration is immutable.') : null;
        static::updating($guard);
        static::deleting($guard);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(FormQuestion::class, 'form_question_id');
    }
}
