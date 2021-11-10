<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignFormInput extends Model
{
    protected $fillable = ['campaign_form_id', 'type', 'label', 'name', 'position', 'is_required', 'min', 'max', 'step', 'options'];

    public function campaign_form()
    {
        return $this->belongsTo(CampaignForm::class);
    }
}
