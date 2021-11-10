<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CurrentCallUserCalled extends Model
{
    public $timestamps = false;

    protected $fillable = ['current_call_id', 'user_id', 'department_id', 'extension', 'start', 'answered'];

    public function user() {
        return $this->belongsTo('App\User');
    }
}
