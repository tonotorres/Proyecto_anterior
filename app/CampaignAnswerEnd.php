<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignAnswerEnd extends Model
{
    protected $fillable = ['company_id', 'name'];

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_campaign_answer_end');
    }
}
