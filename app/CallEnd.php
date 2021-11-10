<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CallEnd extends Model
{
    protected $fillable = ['company_id', 'name', 'position'];

    public function call_types() {
        return $this->belongsToMany('App\CallType', 'call_end_call_type');
    }

    public function departments() {
        return $this->belongsToMany('App\Department', 'call_end_department');
    }
}
