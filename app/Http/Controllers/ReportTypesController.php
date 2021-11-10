<?php

namespace App\Http\Controllers;

use App\ReportType;
use Illuminate\Http\Request;

class ReportTypesController extends Controller
{
    public function api_get_list()
    {        
        $user = get_loged_user();
        return ReportType::select('id', 'name as label')
            ->whereNull('company_id')
            ->orWhere('company_id', $user->company_id)
            ->get();
    }
}
