<?php

namespace App\Http\Controllers;

use App\BreakTimeUser;
use App\Events\EndBreakTimeUser;
use App\Events\StartBreakTimeUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BreakTimeUsersController extends Controller
{
    public function api_get_current_break_time_user() {
        $user = get_loged_user();
        return self::get_current_break_time_user($user->id);
    }

    public function api_get_user_today_report() {
        $user = get_loged_user();
        return self::get_user_today_report($user->id);
    }

    public function api_start($break_time_id) {
        $user = get_loged_user();

        if (!empty($user->extension)) {
            //$freepbx_queues = get_user_queues($user->extension);
            //if (!empty($freepbx_queues)) {
            if (true) {
                $response = pause_all_extension($user->extension);

                Storage::append('break_times/start.log', date('Y-m-d H:i:s') . ': ' . json_encode($response));
                if (!empty($response->action_info) && !empty($response->action_info->Response) && ($response->action_info->Response == 'Success' || $response->action_info->Message == 'Interface not found')) {
                //if (!empty($response->action_info) && !empty($response->action_info->Response) && $response->action_info->Response == 'Success') {
                    $break_time_user =  self::start_break_time($break_time_id, $user->id);

                    broadcast(new StartBreakTimeUser($break_time_user, $user));
                } else {
                    Storage::append('break_times/start.log', date('Y-m-d H:i:s') . ' Iniciamos: ' . json_encode($response) . "\r\n");
                    $break_time_user['error'] = true;
                }
            } else {
                $break_time_user =  self::start_break_time($break_time_id, $user->id);

                broadcast(new StartBreakTimeUser($break_time_user, $user));
            }
        } else {
            $break_time_user =  self::start_break_time($break_time_id, $user->id);

            broadcast(new StartBreakTimeUser($break_time_user, $user));
        }


        return $break_time_user;
    }

    public function api_end($id) {
        $user = get_loged_user();
        $break_time_user = BreakTimeUser::findOrFail($id);

        if (!empty($user->extension)) {
            //$freepbx_queues = get_user_queues($user->extension);
            if (true) {
                $response = unpause_all_extension($user->extension);

                Storage::append('break_times/start.log', date('Y-m-d H:i:s') . ' Finalizamos: ' . json_encode($response) . "\r\n");

                if (!empty($response->action_info) && !empty($response->action_info->Response) && $response->action_info->Response == 'Success') {
                    self::end_break_time($break_time_user);

                    if($user->paused_queues()->count() > 0) {
                        foreach($user->paused_queues as $queue) {
                            pause_queue($queue->number, $user->extension);
                        }
                    }

                    $break_time_user_report = self::get_user_today_report($user->id);

                    broadcast(new EndBreakTimeUser($break_time_user_report, $user));
                } else if (!empty($response->action_info) && !empty($response->action_info->Message) && $response->action_info->Message == "Interface not found") {
                    self::end_break_time($break_time_user);

                    $break_time_user_report = self::get_user_today_report($user->id);

                    broadcast(new EndBreakTimeUser($break_time_user_report, $user));
                } else {
                    Storage::append('break_times/end.log', date('Y-m-d H:i:s') . ': ' . json_encode($response));
                    $break_time_user_report['error'] = true;
                }
            } else {
                self::end_break_time($break_time_user);

                $break_time_user_report = self::get_user_today_report($user->id);

                broadcast(new EndBreakTimeUser($break_time_user_report, $user));
            }
        } else {
            self::end_break_time($break_time_user);

            $break_time_user_report = self::get_user_today_report($user->id);

            broadcast(new EndBreakTimeUser($break_time_user_report, $user));
        }


        return $break_time_user_report;
    }

    private function start_break_time($break_time_id, $user_id) {
        self::end_old_break_times($user_id);

        $break_time_user = new BreakTimeUser();
        $break_time_user->break_time_id = $break_time_id;
        $break_time_user->user_id = $user_id;
        $break_time_user->start = date('YmdHis');
        $break_time_user->save();

        return $break_time_user;
    }

    private function end_old_break_times($user_id) {
        $break_time_users = BreakTimeUser::where('user_id', $user_id)
            ->whereNull('end')
            ->get();

        if(!empty($break_time_users)) {
            foreach ($break_time_users as $break_time_user) {
                self::end_break_time($break_time_user);
            }
        }
    }

    private function end_break_time($break_time_user) {
        $break_time_user->end = date('YmdHis');
        $start = strtotime(convert_int_to_time($break_time_user->start));
        $end = strtotime(convert_int_to_time($break_time_user->end));
        $break_time_user->duration = $end - $start;
        $break_time_user->save();

        return $break_time_user;
    }

    private function get_user_today_report($user_id) {
            $start = date('Ymd').'000000';
            $end = date('Ymd').'235959';

            $break_time_user_report = BreakTimeUser::join('break_times', 'break_time_users.break_time_id', '=', 'break_times.id')
                ->where('start', '>=', $start)
                ->where('start', '<=', $end)
                ->where('user_id', $user_id)
                ->selectRaw('sum(duration) as duration, break_times.name as name')
                ->groupBy('break_time_id', 'break_times.name')
                ->get();

            $object = [];
            foreach($break_time_user_report as $item) {
                $object[] = ['name' => $item->name, 'duration' => $item->duration];
            }

            return $object;
    }

    private function get_current_break_time_user($user_id) {
        $break_time_user = BreakTimeUser::where('user_id', $user_id)
            ->whereNull('end')
            ->first();

        if($break_time_user) {
            $break_time_user->duration = strtotime('now') - strtotime($break_time_user->start);
        }

        return $break_time_user;

    }
}
