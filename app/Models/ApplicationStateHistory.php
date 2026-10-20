<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationStateHistory extends Model
{
    public $timestamps = false;

    protected $table = 'application_state_history';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
