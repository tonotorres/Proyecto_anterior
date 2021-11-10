<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AddressBook extends Model
{
    protected $fillable = ['company_id', 'department_id', 'name'];

    public function department() {
        return $this->belongsTo('App\Department');
    }

    public function address_book_destinations() {
        return $this->hasMany('App\AddressBookDestination');
    }

    public function address_book_options() {
        return $this->hasMany('App\AddressBookOption');
    }
}
