<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CallUserAdministrativeTime extends Model
{
    protected $fillable = ['call_id', 'user_id', 'type', 'duration', 'is_started'];
}
