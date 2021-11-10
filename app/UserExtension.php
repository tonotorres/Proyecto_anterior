<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserExtension extends Model
{
    protected $fillable = ['user_id', 'extension'];

    public function original_extension()
    {
        return $this->belongsTo('App\Extension', 'extension', 'number');
    }
}
