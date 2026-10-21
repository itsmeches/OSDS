<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoIssuanceFormRevision extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['definition' => 'array'];
    }
}
