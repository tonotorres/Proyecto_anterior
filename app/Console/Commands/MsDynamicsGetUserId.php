<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

class MsDynamicsGetUserId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'msdynamicsgetuserid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obteniene el identificador del MsDynamics de todos los usuarios';

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
        $users = User::all();

        foreach ($users as $user) {
            echo date('Y-m-d H:i:s') . ":  Sincornizamos el usuario " . $user->name;
            if (!empty($user->email)) {
                echo " con el email " . $user->email;
                $user_ids = ms_dynamics_get_usercode_by_email($user->email);

                if (!empty($user_ids) && count($user_ids) == 1) {
                    $user->code = $user_ids[0];
                    $user->save();
                    echo " -> código actualizado";
                } elseif (empty($user_ids)) {
                    echo " -> no tenemos níngun usuario con este email";
                } elseif (count($user_ids) > 1) {
                    echo " -> tenemos más de un usuario con este email";
                }
            } else {
                echo "no tenemos email, abortamos solicitud";
            }

            echo "\r\n";
        }
    }
}
