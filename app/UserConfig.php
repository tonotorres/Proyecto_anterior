<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserConfig extends Model
{
    protected $fillable = ['user_id', 'user_config_group_id', 'key', 'label', 'value', 'position'];
}
