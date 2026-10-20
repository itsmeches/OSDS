<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/** Display only: policies and services remain the authorization source. */
class GrantPresentation
{
    public static function state(Model $grant): string
    {
        if ($grant->revoked_at !== null) {
            return 'Revoked';
        }
        if ($grant->starts_at?->isFuture()) {
            return 'Scheduled';
        }

        return $grant->ends_at !== null && ! $grant->ends_at->isFuture() ? 'Expired' : 'Current';
    }

    public static function color(string $state): string
    {
        return match ($state) {
            'Current' => 'success', 'Scheduled' => 'info', 'Expired' => 'warning', default => 'gray',
        };
    }
}
