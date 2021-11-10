<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProjectComment extends Model
{
    protected $fillable = ['project_id', 'user_id', 'comment'];

    public function user() {
        return $this->belongsTo('App\User')->withTrashed();
    }
}
