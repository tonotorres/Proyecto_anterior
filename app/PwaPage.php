<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PwaPage extends Model
{
    protected $fillable = ['company_id', 'name', 'url', 'is_private'];

    public function pwa_elements() {
        return $this->hasMany('App\PwaElement')->orderBy('position');
    }
}
