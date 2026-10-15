<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class EvidenceReferenceRevision extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['locator_ciphertext', 'locator_fingerprint', 'encryption_key_version', 'applicant_label_ciphertext'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->public_id ??= (string) Str::uuid());
        static::updating(fn () => throw new LogicException('Evidence revisions are append-only.'));
        static::deleting(fn () => throw new LogicException('Evidence revisions are append-only.'));
    }

    public function reference(): BelongsTo
    {
        return $this->belongsTo(EvidenceReference::class, 'evidence_reference_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(EvidenceProviderType::class, 'evidence_provider_type_id');
    }
}
