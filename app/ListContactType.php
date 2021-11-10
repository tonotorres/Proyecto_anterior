<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ListContactType extends Model
{
    protected $fillable = ['company_id', 'contact_type_id', 'module_key', 'name', 'value', 'reference_id'];
}
