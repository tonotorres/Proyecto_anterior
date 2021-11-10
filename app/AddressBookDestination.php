<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AddressBookDestination extends Model
{
    protected $fillable = ['address_book_id', 'module_id', 'reference_id', 'destination'];

    public function address_book() {
        return $this->belongsTo('App\AddressBook');
    }
}
