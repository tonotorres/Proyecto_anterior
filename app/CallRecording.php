<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CallRecording extends Model
{
    protected $fillable = ['call_id', 'recordingfile'];
}
