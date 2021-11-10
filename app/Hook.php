<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Hook extends Model
{
    protected $fillable = ['company_id', 'key', 'code', 'position', 'is_active'];

    protected $hook_keys = [
        'BEFORE_WHATSAPP_MESSAGE',
        'AFTER_WHATSAPP_MESSAGE',
    ];
}
