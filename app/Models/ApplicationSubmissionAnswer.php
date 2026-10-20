<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationSubmissionAnswer extends Model
{
    public function question(): BelongsTo
    {
        return $this->belongsTo(FormQuestion::class, 'form_question_id');
    }

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['value' => 'array', 'is_applicable' => 'boolean'];
    }
}
