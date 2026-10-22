<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationDecisionStage extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['attested' => 'boolean', 'created_at' => 'immutable_datetime'];
    }
}
