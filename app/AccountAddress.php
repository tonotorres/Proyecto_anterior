<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountAddress extends Model
{
    use SoftDeletes;
    protected $fillable = ['account_id', 'country_id', 'region_id', 'address', 'number', 'address_aux', 'postal_code', 'location', 'main'];

    public function account() {
        return $this->belongsTo('App\Account');
    }

    public function country() {
        return $this->belongsTo('App\Country');
    }

    public function region() {
        return $this->belongsTo('App\Region');
    }
}
