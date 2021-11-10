<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PwaLanguage extends Model
{
    use SoftDeletes;
    protected $fillable = ['company_id', 'code', 'name'];
}
