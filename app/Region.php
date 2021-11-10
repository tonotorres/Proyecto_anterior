<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use SoftDeletes;
    protected $fillable = ['country_id', 'code', 'region'];

    public function country() {
        return $this->belongsTo('App\Country');
    }
}
