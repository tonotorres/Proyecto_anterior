<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IvrOption extends Model
{
    protected $fillable = ['ivr_id', 'tag_id', 'option'];

    public function tag() {
        return $this->belongsTo('App\Tag');
    }

    public function ivr() {
        return $this->belongsTo('App\Ivr');
    }
}
