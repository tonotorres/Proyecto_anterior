<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    protected $fillable = ['company_id', 'call_type_id', 'call_status_id', 'call_end_id', 'route_in_id', 'route_out_id', 'department_id', 'account_id', 'campaign_id', 'campaign_contact_id', 'from', 'to', 'uniqueid', 'linkedid', 'start', 'duration_wait', 'duration', 'administrative_time', 'recordingfile'];

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
        return $this->belongsTo('App\Account')->withTrashed();
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
        return $this->hasMany('App\CallUser');
    }

    public function call_user_calleds() {
        return $this->hasMany('App\CallUserCalled');
    }

    public function call_comments() {
        return $this->hasMany('App\CallComment');
    }

    public function call_logs() {
        return $this->hasMany('App\CallLog');
    }

    public function call_ivrs() {
        return $this->hasMany('App\CallIvr');
    }

    public function call_queues()
    {
        return $this->hasMany('App\CallQueue');
    }

    public function call_recordings() {
        return $this->hasMany('App\CallRecording');
    }

    public function call_external_code()
    {
        return $this->hasOne(CallExternalCode::class);
    }
}
