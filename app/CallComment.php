<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CallComment extends Model
{
    protected $fillable = ['call_id', 'user_id', 'comment'];

    public function user() {
        return $this->belongsTo('App\User');
    }
}
