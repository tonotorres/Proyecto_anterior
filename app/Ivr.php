<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ivr extends Model
{
    protected $fillable = ['company_id', 'tag_id', 'pbx_id', 'name'];

    public function tag() {
        return $this->belongsTo('App\Tag');
    }

    public function ivr_options() {
        return $this->hasMany('App\IvrOption');
    }
}
