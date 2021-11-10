<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'name'];

    public function report_items() {
        return $this->hasMany('App\ReportItem');
    }
}
