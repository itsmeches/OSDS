<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyntheticEvidenceReviewRequirement extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(DocumentaryRequirement::class, 'documentary_requirement_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SyntheticEvidenceReviewItem::class, 'case_requirement_id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(SyntheticEvidenceReviewFinding::class, 'case_requirement_id');
    }
}
