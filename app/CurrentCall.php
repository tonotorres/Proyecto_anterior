<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CurrentCall extends Model
{
    protected $fillable = ['company_id', 'call_type_id', 'call_status_id', 'call_end_id', 'route_in_id', 'route_out_id', 'department_id', 'account_id', 'contact_id', 'from', 'to', 'uniqueid', 'linkedid', 'start', 'duration_wait', 'duration', 'administrative_time'];

    public function call_type() {
        return $this->belongsTo('App\CallType');
    }

    public function call_status() {
        return $this->belongsTo('App\CallStatus');
    }

    public function call_end() {
        return $this->belongsTo('App\CallEnd');
    }

    public function department() {
        return $this->belongsTo('App\Department');
    }

    public function account() {
        return $this->belongsTo('App\Account');
    }

    public function campaign()
    {
        return $this->belongsTo('App\Campaign');
    }

    public function campaign_contact()
    {
        return $this->belongsTo('App\CampaignContact');
    }

    public function call_users() {
        return $this->hasMany('App\CurrentCallUser');
    }

    public function active_call_users()
    {
        return $this->hasMany('App\CurrentCallUser')->whereNull('duration');
    }

    public function call_user_calleds() {
        return $this->hasMany('App\CurrentCallUserCalled');
    }

    public function call_comments() {
        return $this->hasMany('App\CurrentCallComment');
    }

    public function call_logs() {
        return $this->hasMany('App\CurrentCallLog');
    }

    public function call_ivrs() {
        return $this->hasMany('App\CurrentCallIvr');
    }

    public function call_queues()
    {
        return $this->hasMany('App\CurrentCallQueue');
    }

    public function channels()
    {
        return $this->hasMany('App\PbxChannel');
    }

    public function transfers()
    {
        return $this->hasMany('App\CurrentCallTransfer');
    }
}
