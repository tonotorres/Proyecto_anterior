<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RouteIn extends Model
{
    protected $fillable = ['company_id', 'department_id', 'number', 'name'];

    public function department() {
        return $this->belongsTo('App\Extension');
    }
}
