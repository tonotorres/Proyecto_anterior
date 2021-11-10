<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Extension extends Model
{
    protected $fillable = ['company_id', 'department_id', 'extension_status_id', 'number', 'name'];

    public function department() {
        return $this->belongsTo('App\Department');
    }

    public function extension_status() {
        return $this->belongsTo('App\ExtensionStatus');
    }

    public function current_call_users()
    {
        return $this->hasMany('App\CurrentCallUser', 'extension', 'number')->whereNull('current_call_users.duration');
    }
}
