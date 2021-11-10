<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AddressBookOption extends Model
{
    protected $fillable = ['address_book_id', 'pbx_ivr_id', 'option', 'overflow'];

    public function address_book() {
        return $this->belongsTo('App\AddressBook');
    }
}
