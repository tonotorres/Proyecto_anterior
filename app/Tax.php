<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use SoftDeletes;
    protected $fillable = ['tax_group_id', 'name', 'value'];

    public function tax_group() {
        return $this->belongsTo('App\TaxGroup');
    }
}
