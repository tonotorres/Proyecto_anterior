<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TpvPayment extends Model
{
    protected $fillable = ['company_id', 'code', 'price', 'request_code', 'response_code', 'is_correct'];
}
