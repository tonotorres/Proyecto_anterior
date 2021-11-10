<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectStatus extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'name', 'weight', 'color', 'finish'];
}
