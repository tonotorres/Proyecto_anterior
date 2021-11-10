<?php

namespace App\Jobs;

use App\Contact;
use App\CurrentCall;
use App\CurrentCallLog;
use App\CurrentCallUser;
use App\Events\CallStart as EventsCallStart;
use App\Events\CallUpdate;
use App\Events\UpdateUserStatus;
use App\ListContactType;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CallStart implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payload;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(!empty($this->payload['linkedid']) && !empty($this->payload['uniqueid']) && !empty($this->payload['company_id']) && !empty($this->payload['call_type_id']) && !empty($this->payload['callerid']) && !empty($this->payload['ddi']) && !empty($this->payload['start']) && $this->payload['callerid'] != 'failed') {
            //RC: Miramos si tenemos la llamada ya registrada
            $current_call = CurrentCall::where('linkedid', $this->payload['linkedid'])
                ->where('company_id', $this->payload['company_id'])
                ->first();

            if(empty($current_call)) {
                //RC: Miramos si es de un cotnacto o de una cuenta
                $list_contact_type = ListContactType::where('value', $this->payload['callerid'])
                    ->orWhere('value', substr($this->payload['callerid'], 1))
                    ->orWhere('value', str_replace('+', '00', $this->payload['callerid']))
                    ->first();

                $account_id = null;
                $contact_id = null;
                if(!empty($list_contact_type)) {
                    if($list_contact_type->module_key == 9) {
                        $account_id = $list_contact_type->reference_id;
                    } elseif($list_contact_type->module_key == 7) {
                        $contact = Contact::where('id', $list_contact_type->reference_id)->first();
                        $contact_id = $contact->id;
                        if(!empty($contact->account_id)) {
                            $account_id = $contact->account_id;
                        }
                    }
                }

                //RC: Generamos el registro
                $data['company_id'] = $this->payload['company_id'];
                $data['call_type_id'] = $this->payload['call_type_id'];
                $data['call_status_id'] = 1;
                $data['account_id'] = $account_id;
                $data['contact_id'] = $contact_id;
                $data['uniqueid'] = $this->payload['uniqueid'];
                $data['linkedid'] = $this->payload['linkedid'];
                if($this->payload['call_type_id'] == 1) {
                    $data['from'] = $this->payload['callerid'];
                    $data['to'] = $this->payload['ddi'];
                } else {
                    $data['from'] = $this->payload['ddi'];
                    $data['to'] = $this->payload['callerid'];
                }
                $data['start'] = strtotime($this->payload['start']);
                $current_call = CurrentCall::create($data);

                //RC: Guardamos un registro en el log
                $data_log['current_call_id'] = $current_call->id;
                $data_log['call_log_type_id'] = 1;
                $data_log['description'] = 'Inicio de la llamada';
                $data_log['start'] = strtotime($this->payload['start']);
                CurrentCallLog::create($data_log);

                if($current_call->call_type_id == 3) {
                    //RC: Si tenemos una llamada saliente o interna tenemos que asignar 
                    $user = User::where('extension', $this->payload['ddi'])
                        ->where('company_id', $this->payload['company_id'])
                        ->first();
                    
                    //RC: Guardamos el usuario nuevo
                    $data['current_call_id'] = $current_call->id;
                    if(!empty($user)) {
                        $data['user_id'] = $user->id;
                        $data['user_name'] = $user->id;
                        if(!empty($user->department_id)) {
                            $data['department_id'] = $user->department_id;
                            
                            $current_call->department_id = $user->department_id;
                            $current_call->save();
                        } else {
                            $data['department_id'] = null;
                        }
                    }
                    $data['extension'] = $this->payload['ddi'];
                    $data['start'] = strtotime($this->payload['start']);
                    CurrentCallUser::create($data);

                    if(!empty($user)) {
                        broadcast(new UpdateUserStatus($user));
                    }

                    //RC: Asignamos la llamada como activa
                    $current_call->call_status_id = 2;
                    $current_call->save();

                    //RC: Generamos el registro del log
                    $data_log['current_call_id'] = $current_call->id;
                    $data_log['call_log_type_id'] = 6;
                    $data_log['description'] = 'Conectamos con '.(!empty($user) ? $user->name.' ' : '').'('.$this->payload['ddi'].')';
                    $data_log['start'] = strtotime($this->payload['start']);
                    CurrentCallLog::create($data_log);
                }

                //RC: Emitimos el evento de nueva llamada
                $call_stat['id'] = $current_call->id;
                $call_stat['from'] = $current_call->from;
                $call_stat['to'] = $current_call->to;
                $call_stat['start'] = $current_call->start;
                $call_stat['duration'] = strtotime('now') - $current_call->start;
                if($current_call->call_type_id == 3) {
                    if(!empty($user)) {
                        $call_stat['user_id'] = $user->id;
                        $call_stat['user_name'] = $user->name;
                        if(!empty($user->department_id)) {
                            $call_stat['department_id'] = $user->department_id;
                        } else {
                            $call_stat['department_id'] = null;
                        }
                    } else {
                        $call_stat['user_id'] = null;
                        $call_stat['user_name'] = null;
                        $call_stat['department_id'] = null;
                    }
                    $call_stat['extension'] = $current_call->ddi;
                } else {
                    $call_stat['user_id'] = null;
                    $call_stat['user_name'] = null;
                    $call_stat['department_id'] = null;
                    $call_stat['extension'] = $current_call->ddi;
                }
                $call_stat['queue'] = null;
                $call_stat['call_type_id'] = $current_call->call_type_id;
                $call_stat['call_status_id'] = $current_call->call_status_id;

                broadcast(new EventsCallStart($call_stat, $current_call, $current_call->company_id));

            } else if($current_call->call_type_id == 3 && $this->payload['call_type_id'] == 2) {
                $current_call->call_type_id = 2;
                $current_call->save();

                //RC: Emitimos el evento de nueva llamada
                $call_stat['id'] = $current_call->id;
                $call_stat['from'] = $current_call->from;
                $call_stat['to'] = $current_call->to;
                $call_stat['start'] = $current_call->start;
                $call_stat['duration'] = strtotime('now') - $current_call->start;
                $call_stat['user_id'] = null;
                $call_stat['user_name'] = null;
                $call_stat['department_id'] = null;
                $call_stat['extension'] = null;
                $call_stat['queue'] = null;
                $call_stat['call_type_id'] = $current_call->call_type_id;
                $call_stat['call_status_id'] = $current_call->call_status_id;

                broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));

            }
        }
    }
}
