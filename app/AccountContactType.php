<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountContactType extends Model
{
    use SoftDeletes;

    protected $fillable = ['account_id', 'contact_type_id', 'name', 'value'];

    public function account() {
        return $this->belongsTo('App\Account');
    }
}
