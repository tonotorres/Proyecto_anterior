<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountDescription extends Model
{
    protected $fillable = ['account_id', 'description'];
}
