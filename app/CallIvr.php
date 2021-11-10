<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CallIvr extends Model
{
    public $timestamps = false;

    protected $fillable = ['call_id', 'pbx_ivr', 'option', 'start'];
}
