<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CurrentCallQueue extends Model
{
    protected $fillable = ['current_call_id', 'queue', 'start'];
}
