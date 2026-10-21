<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DownloadableForm extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['withdrawn_at' => 'datetime'];
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DownloadableFormVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DownloadableFormVersion::class)->orderByDesc('version_number');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(OsdsModule::class);
    }
}
