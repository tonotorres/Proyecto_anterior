<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'account_id', 'code', 'name', 'start', 'end', 'active', 'finished_at'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function campaign_in_call()
    {
        return $this->hasOne(CampaignInCall::class);
    }

    public function campaign_out_call()
    {
        return $this->hasOne(CampaignOutCall::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'campaign_user');
    }

    public function campaign_forms()
    {
        return $this->belongsToMany(CampaignForm::class, 'campaign_campaign_form');
    }

    public function campaign_answer_ends()
    {
        return $this->belongsToMany(CampaignAnswerEnd::class, 'campaign_campaign_answer_end');
    }

    public function campaign_urls()
    {
        return $this->hasMany(CampaignUrl::class);
    }
}
