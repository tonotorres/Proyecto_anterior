<?php

namespace App\Console\Commands;

use App\Events\LogoutUser;
use App\Events\UserKeepAlive;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CloseUsersSession extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'closeusersession';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cierra todas las sesiones de los usuarios que no tienen el panel abierto';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $limit_date = date('Y-m-d H:i:s', strtotime('-10 minutes'));

        //RC: Finalizamos la sesiones de los usuarios con más de 10 minutos des de la última notificación de conexión
        $users = User::join('user_sessions', 'user_sessions.user_id', '=', 'users.id')
            ->whereNull('user_sessions.duration')
            ->select('users.*')
            ->where('keep_alive_at', '<=', $limit_date)
            ->where('always_online', '0')
            ->get();

        foreach ($users as $user) {
            $user->api_token = Str::random(100);
            $user->save();

            if (!empty($user->extension)) {
                pause_all_extension($user->extension);
            }

            $latitude = '';
            $longitude = '';

            broadcast(new LogoutUser($user));
            end_old_break_times($user->id);
            end_user_session($user->id, $latitude, $longitude);
        }

        //RC: Emitimos el evento para todos los usuarios conectados
        $users = User::join('user_sessions', 'user_sessions.user_id', '=', 'users.id')
        ->whereNull('user_sessions.duration')
        ->select('users.*')
            ->where('always_online', '0')
            ->get();

        foreach ($users as $user) {
            broadcast(new UserKeepAlive($user->load('user_extensions', 'user_extensions.original_extension', 'active_session')));
        }
    }
}
