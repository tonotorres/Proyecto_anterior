<?php

namespace App\Http\Controllers;

use App\CallType;
use Illuminate\Http\Request;

class CallTypesController extends Controller
{
    public function api_get_list()
    {
        
        return CallType::select('id', 'name as label')
            ->get();
    }
}
