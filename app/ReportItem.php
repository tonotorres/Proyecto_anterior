<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReportItem extends Model
{
    protected $fillable = ['report_id', 'report_type_id', 'name', 'size', 'position'];

    public function report() {
        return $this->belongsTo('App\Report');
    }

    public function report_type() {
        return $this->belongsTo('App\ReportType');
    }
}
