<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExtensionStatusLog extends Model
{
    protected $fillable = ['extension_id', 'extension_status_id'];

    public function extension_status() {
        return $this->belongsTo('App\ExtensionStatus');
    }

    public function extension() {
        return $this->belongsTo('App\Extension');
    }
}
