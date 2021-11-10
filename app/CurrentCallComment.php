<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CurrentCallComment extends Model
{
    protected $fillable = ['current_call_id', 'user_id', 'comment'];

    public function user() {
        return $this->belongsTo('App\User');
    }
}
