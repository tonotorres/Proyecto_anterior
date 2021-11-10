<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReportCall extends Model
{
    protected $timestamps = false;

    protected $fillable = ['company_id', 'year', 'month', 'day', 'hour', 'call_type_id', 'call_status_id', 'call_end_id', 'ddi_id', 'total', 'duration'];
}
