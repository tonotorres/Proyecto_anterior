<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignContact extends Model
{
    protected $fillable = ['campaign_id', 'name', 'last_name', 'birthday', 'nif', 'phone_1', 'phone_2', 'email_1', 'email_2', 'address', 'address_aux', 'postal_code', 'location', 'region', 'country'];

    public function campaign_answers()
    {
        return $this->hasMany(CampaignAnswer::class)
            ->orderBy('id', 'ASC');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
