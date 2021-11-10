<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReportWaitCall extends Model
{
    protected $timestamps = false;

    protected $fillable = ['company_id', 'year', 'month', 'day', 'hour', 'call_status_id', 'call_end_id', 'ddi_id', 'range', 'total'];
}
