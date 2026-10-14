<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvidenceProviderType extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }
}
