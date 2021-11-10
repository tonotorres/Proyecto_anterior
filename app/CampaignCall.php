<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignCall extends Model
{
    protected $fillable = ['campaign_id', 'phone', 'name', 'weight', 'retries', 'total_retries', 'is_paused', 'is_blocked', 'is_correct'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
