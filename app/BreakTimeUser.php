<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BreakTimeUser extends Model
{
    protected $fillable = ['break_time_id', 'user_id'];

    public function break_time() {
        return $this->belongsTo('App\BreakTime');
    }

    public function user() {
        return $this->belongsTo('App\User');
    }
}
