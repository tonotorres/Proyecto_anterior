<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class CheckApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = get_loged_user();
        $gc_token = Cookie::get('gctoken');

        if(empty($gc_token) || base64_decode($gc_token) != $user->api_token) {
            Auth::logout();
            return redirect('/');
        }

        return $next($request);
    }
}
