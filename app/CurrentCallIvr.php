<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CurrentCallIvr extends Model
{
    public $timestamps = false;
    protected $fillable = ['current_call_id', 'pbx_ivr', 'option', 'start'];
}
