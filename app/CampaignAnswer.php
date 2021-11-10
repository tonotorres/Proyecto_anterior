<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignAnswer extends Model
{
    public function campaign_answer_json()
    {
        return $this->hasOne(CampaignAnswerJson::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function campaign_form()
    {
        return $this->belongsTo(CampaignForm::class);
    }

    public function campaign_contact()
    {
        return $this->belongsTo(CampaignContact::class);
    }

    public function campaign_answer_end()
    {
        return $this->belongsTo(CampaignAnswerEnd::class);
    }
}
