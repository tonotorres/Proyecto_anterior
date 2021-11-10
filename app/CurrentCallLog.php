<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CurrentCallLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['current_call_id', 'call_log_type_id', 'reference_id', 'description', 'start'];
}
