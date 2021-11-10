<?php

namespace App\Http\Controllers;

use App\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserSessionsController extends Controller
{
    public function api_get_active_users() {
        $user = get_loged_user();
        return UserSession::join('users', 'users.id', '=', 'user_sessions.user_id')
            ->join('user_company', 'user_company.user_id', '=', 'users.id')
            ->where('user_company.company_id', $user->company_id)
            ->whereNull('user_sessions.end')
            ->select('user_sessions.*')
            ->get()->load('user', 'user.department');
    }

    public function api_get_departments_active_users() {
        $user = get_loged_user();

        return DB::select("
            SELECT departments.id, departments.name, count(user_sessions.id) as total 
            FROM departments 
            LEFT JOIN users ON users.department_id = departments.id AND users.deleted_at is null
            LEFT JOIN user_sessions ON user_sessions.user_id = users.id AND user_sessions.end is null 
            WHERE departments.company_id = ".$user->company_id."
            GROUP BY departments.id, departments.name
        ");
    }

    public function api_get_report(Request $request) {
        $user = get_loged_user();
        
        if(!empty($request->start)) {
            $start = date('Y-m-d', strtotime($request->start));
        } else {
            $start = date('Y-m-d');
        }

        if(!empty($request->end)) {
            $end = date('Y-m-d', strtotime($request->end));
        } else {
            $end = date('Y-m-d');
        }

        $user_sessions = UserSession::join('users', 'users.id', '=', 'user_sessions.user_id')
            ->join('user_company', 'user_company.user_id', '=', 'users.id')
            ->where('user_company.company_id', $user->company_id)
            ->whereDate('user_sessions.start', '>=', $start)
            ->whereDate('user_sessions.start', '<=', $end)
            ->select('user_sessions.*');
        
        if(!empty($request->user_id)) {
            $user_sessions->where('user_sessions.user_id', $request->user_id);
        }

        if(!empty($request->department_id)) {
            $user_sessions->where('users.department_id', $request->department_id);
        }

        return $user_sessions->get()->load('user', 'user.department');

    }
}
