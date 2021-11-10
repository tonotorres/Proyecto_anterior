<?php

namespace App\Http\Controllers;

use App\CallStatus;
use Illuminate\Http\Request;

class CallStatusesController extends Controller
{
    public function api_get_list()
    {
        
        return CallStatus::select('id', 'name as label')
            ->get();
    }
}
