<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SpaController extends Controller
{
    public function v1() {
        return view('spa.v1', [
            'user' => Auth::user()->load('user_extensions', 'user_extensions.original_extension', 'active_session', 'campaigns')
        ]);
    }

    public function externalv1()
    {
        return view('spa.externalv1');
    }
}
