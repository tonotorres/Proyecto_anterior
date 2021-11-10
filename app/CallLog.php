<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['call_id', 'call_log_type_id', 'reference_id', 'description', 'start'];
}
