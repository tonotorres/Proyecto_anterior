<?php

namespace App\Http\Controllers;

use App\BreakTimeUser;
use App\Events\LogoutUser;
use App\User;
use App\UserSession;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Storage;

class LogoutController extends Controller
{
    public function logout(Request $request) {
        try {
            $user = Auth::user();
            if(!empty($user->extension)) {
                pause_all_extension($user->extension);
            }   
            
            end_old_break_times($user->id);
            end_user_session($user->id, '', '');
            
            broadcast(new LogoutUser($user));
            
            Auth::logout();

            return redirect('/login');
        } catch(Exception $e) {
            return redirect('/login');
        }
    }

    public function api_logout(Request $request) {
        $user = Auth::user();
        if(!empty($user->extension)) {
            $response = pause_all_extension($user->extension);
            Storage::append('break_times/start.log', date('Y-m-d H:i:s') . ': ' . json_encode($response));
            if (!empty($response->action_info) && !empty($response->action_info->Response) && ($response->action_info->Response == 'Success' || $response->action_info->Message == 'Interface not found')) {
            } else {
                return ['error' => true];
            }
        }

        if(!empty($request->latitude)) {
            $latitude = $request->latitude;
        } else {
            $latitude = '';
        }

        if(!empty($request->longitude)) {
            $longitude = $request->longitude;
        } else {
            $longitude = '';
        }

        broadcast(new LogoutUser($user));
        end_old_break_times($user->id);
        end_user_session($user->id, $latitude, $longitude);


        return ['error' => false];
    }

    public function api_force_logout(int $user_id) {
        $user = User::findOrFail($user_id);
        
        if(!empty($user->extension)) {
            pause_all_extension($user->extension);
        }

        broadcast(new LogoutUser($user));
        end_old_break_times($user->id);
        end_user_session($user->id, '', '');

        $user->api_token = Str::random(100);
        $user->save();

        return ['error' => false];
    }
}
