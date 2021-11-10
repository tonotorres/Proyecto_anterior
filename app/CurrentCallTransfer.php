<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CurrentCallTransfer extends Model
{
    protected $fillable = ['current_call_id', 'from', 'to'];
}
