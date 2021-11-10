<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DelayedReport extends Model
{
    protected $fillable = ['company_id', 'name', 'data', 'finished'];
}
