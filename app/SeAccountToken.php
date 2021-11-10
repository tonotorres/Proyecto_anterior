<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SeAccountToken extends Model
{
    protected $fillable = ['account_id', 'token'];
}
