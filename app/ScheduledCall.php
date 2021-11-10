<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScheduledCall extends Model
{
    protected $fillable = ['company_id', 'campaign_id', 'user_id', 'call_date', 'phone', 'subject', 'notified_at'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
