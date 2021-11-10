<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignUrl extends Model
{
    protected $fillable = ['campaign_id', 'token', 'url', 'active'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
