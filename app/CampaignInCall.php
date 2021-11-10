<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignInCall extends Model
{
    protected $fillable = ['campaign_id', 'start', 'end', 'queue', 'administrative_time', 'active'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
