<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Application extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->public_id ??= (string) Str::uuid());
    }

    public function currentWorkingRevision(): BelongsTo
    {
        return $this->belongsTo(ApplicationWorkingRevision::class, 'current_working_revision_id');
    }

    public function currentSubmission(): BelongsTo
    {
        return $this->belongsTo(ApplicationSubmission::class, 'current_submission_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ApplicationSubmission::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(ApplicationCorrectionRequest::class)->orderByDesc('requested_at');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(OsdsModule::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'routing_region_id');
    }

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(PublicationManifest::class, 'publication_manifest_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ApplicationStateHistory::class);
    }
}
