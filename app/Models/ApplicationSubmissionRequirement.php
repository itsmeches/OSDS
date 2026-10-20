<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationSubmissionRequirement extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_applicable' => 'boolean', 'is_required' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ApplicationSubmissionEvidenceItem::class, 'submission_requirement_id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(DocumentaryRequirement::class, 'documentary_requirement_id');
    }
}
