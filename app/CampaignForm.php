<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignForm extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'code', 'name'];

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_campaign_form');
    }

    public function campaign_form_inputs()
    {
        return $this->hasMany(CampaignFormInput::class)
            ->orderBy('position', 'asc')
            ->get();
    }
}
