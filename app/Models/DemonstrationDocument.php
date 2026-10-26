<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemonstrationDocument extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['source_snapshot' => 'array', 'render_snapshot' => 'array', 'started_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime'];
    }
}
