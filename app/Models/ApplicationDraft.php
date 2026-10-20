<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ApplicationDraft extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_saved_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
    }

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(PublicationManifest::class, 'publication_manifest_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(OsdsModule::class, 'module_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ApplicationDraftAnswer::class);
    }

    public function evidenceReferences(): HasMany
    {
        return $this->hasMany(EvidenceReference::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
