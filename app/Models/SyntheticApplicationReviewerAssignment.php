<?php

namespace App\Models;

use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Auditing\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class SyntheticApplicationReviewerAssignment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (! app(ApplicantSandboxGuard::class)->allowed() || (auth()->check() && ! auth()->user()->can('applications.reviewer.assignments.manage')) || ! User::findOrFail($model->reviewer_id)->is_synthetic) {
                throw new AuthorizationException;
            }
            $model->public_id ??= (string) Str::uuid();
            $model->granted_by ??= auth()->id();
        });
        static::updating(function (self $model): void {
            if ($model->isDirty('revoked_at') && $model->revoked_at) {
                $model->revoked_by ??= auth()->id();
                if (! $model->revocation_reason) {
                    throw new LogicException('A revocation reason is required.');
                }
            }
        });
        static::created(fn (self $model) => app(AuditRecorder::class)->record('application.reviewer_assignment_granted', 'success', auth()->user(), $model, $model->region_id, metadata: ['reviewer_id' => $model->reviewer_id, 'module_id' => $model->module_id]));
        static::updated(function (self $model): void {
            if ($model->wasChanged('revoked_at')) {
                app(AuditRecorder::class)->record('application.reviewer_assignment_revoked', 'success', auth()->user(), $model, $model->region_id, metadata: ['reviewer_id' => $model->reviewer_id, 'module_id' => $model->module_id]);
            }
        });
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(OsdsModule::class, 'module_id');
    }
}
