<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationSubmissionEvidenceItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function revision(): BelongsTo
    {
        return $this->belongsTo(EvidenceReferenceRevision::class, 'evidence_reference_revision_id');
    }
}
