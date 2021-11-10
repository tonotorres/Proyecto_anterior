<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxGroup extends Model
{
    use SoftDeletes;
    protected $fillable = ['name'];

    public function taxes() {
        return $this->hasMany('App\Tax');
    }
}
