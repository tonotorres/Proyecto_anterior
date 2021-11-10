<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignOutCall extends Model
{
    protected $fillable = ['campaign_id', 'route_out_id', 'start', 'end', 'start_time', 'end_time', 'administrative_time', 'active'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function route_out()
    {
        return $this->belongsTo(RouteOut::class);
    }
}
