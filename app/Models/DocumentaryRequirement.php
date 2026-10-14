<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LogicException;

class DocumentaryRequirement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'condition_rules' => 'array'];
    }

    protected static function booted(): void
    {
        $guard = fn (self $m) => in_array($m->version->state, ['published', 'retired'], true) ? throw new LogicException('Published configuration is immutable.') : null;
        static::updating($guard);
        static::deleting($guard);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(RequirementSetVersion::class, 'requirement_set_version_id');
    }

    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(EvidenceProviderType::class, 'documentary_requirement_provider');
    }
}
