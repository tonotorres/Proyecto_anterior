<?php

namespace App\Console\Commands;

use App\Queue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateQueuePauses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updatequeuepauses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar los estado de las extensiones que tienen usuarios';

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
        $users = DB::select("
            SELECT u1.*
            FROM users u1 
            WHERE u1.deleted_at IS NULL 
            AND u1.extension IS NOT NULL 
            AND NOT EXISTS (
                SELECT * 
                FROM users u2 
                WHERE u1.extension = u2.extension 
                AND u1.id <> u2.id 
                AND u2.deleted_at IS NULL 
                AND u1.company_id = u2.company_id
            )
            ORDER BY u1.extension
        ");
        
        foreach($users as $user) {
            $user_status = get_user_status($user);
            echo 'Usuario: '.$user->id.' '.$user->name." Estado: ".$user_status['status_type']."\r\n";

            $freepbx_queues = get_user_queues($user->extension);

            if(!empty($freepbx_queues)) {
                foreach($freepbx_queues as $freepbx_queue) {
                    echo "Cola: ".$freepbx_queue->number." Estado: ".$freepbx_queue->paused."\r\n";
                    $queue = Queue::where('number', $freepbx_queue->number)
                        ->where('company_id', $user->company_id)
                        ->first();

                    //RC: Si no tenemos la cola en el sistema la tenemos que generar
                    if (empty($queue)) {
                        $queue = new Queue();
                        $queue->company_id = $user->company_id;
                        $queue->name = $freepbx_queue->number;
                        $queue->number = $freepbx_queue->number;
                        $queue->save();
                    }

                    if($user_status['status_type'] == 'break_time' || $user_status['status_type'] == 'offline') {
                        //RC: Las colas deben estar pausadas
                        if(!$freepbx_queue->paused) {
                            echo "Tenemos que pausar la cola\r\n";
                            pause_queue($queue->number, $user->extension);
                        }
                    } elseif($user_status['status_type'] == 'online') {
                        //RC: Las colas deben estar 
                        if($freepbx_queue->paused) {
                            if($queue->paused_users()->where('id', $user->id)->count() == 0) {
                                echo "Tenemos que quitar la pausa\r\n";
                                unpause_queue($queue->number, $user->extension);
                            }
                        }
                    }
                }
            }
        }
    }
}
