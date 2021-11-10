<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactContactType extends Model
{
    use SoftDeletes;

    protected $fillable = ['contact_id', 'contact_type_id', 'name', 'value'];

    public function contact() {
        return $this->belongsTo('App\Contact');
    }
}
