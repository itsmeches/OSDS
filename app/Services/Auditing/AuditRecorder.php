<?php

namespace App\Services\Auditing;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditRecorder
{
    public function record(
        string $action,
        string $outcome,
        ?User $actor = null,
        ?Model $resource = null,
        ?int $regionId = null,
        ?int $institutionId = null,
        array $metadata = [],
        ?string $correlationId = null,
    ): AuditEvent {
        return AuditEvent::query()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'resource_type' => $resource?->getMorphClass(),
            'resource_id' => $resource === null ? null : (string) $resource->getKey(),
            'region_id' => $regionId,
            'institution_id' => $institutionId,
            'outcome' => $outcome,
            'correlation_id' => $correlationId ?? (string) Str::uuid(),
            'metadata' => $this->sanitize($metadata),
        ]);
    }

    private function sanitize(array $metadata): array
    {
        $blocked = ['password', 'password_confirmation', 'token', 'secret', 'authorization', 'cookie', 'url', 'locator', 'file_id', 'ciphertext', 'fingerprint', 'encryption_key'];

        return collect($metadata)
            ->reject(function ($value, $key) use ($blocked): bool {
                $key = strtolower((string) $key);

                return collect($blocked)->contains(fn (string $term): bool => str_contains($key, $term));
            })
            ->map(fn ($value) => is_array($value) ? $this->sanitize($value) : $value)
            ->all();
    }
}
