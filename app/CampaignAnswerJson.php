<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignAnswerJson extends Model
{
    public function getFieldsAttribute($value)
    {
        return json_decode($value);
    }
}
