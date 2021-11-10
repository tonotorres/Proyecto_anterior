<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CallQueue extends Model
{
    protected $fillable = ['call_id', 'queue', 'start'];
}
