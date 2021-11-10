<?php

namespace App\Jobs;

use App\Contact;
use App\CurrentCall;
use App\CurrentCallLog;
use App\Events\CallUpdate;
use App\Events\UpdateUserStatus;
use App\ListContactType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CallSetCallerid implements ShouldQueue
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
        if(!empty($this->payload['company_id']) && !empty($this->payload['linkedid']) && !empty($this->payload['from']) && !empty($this->payload['to']) && !empty($this->payload['start'])) {
            //RC: Miramos si tenemos la llamada
            $current_call = CurrentCall::where('linkedid', $this->payload['linkedid'])
                ->where('company_id', $this->payload['company_id'])
                ->first();

            if(!empty($current_call) && $current_call->call_type_id >= 2) {
                if($current_call->from  != $this->payload['from'] || $current_call->to  != $this->payload['to']) {
                    //RC: Miramos si es de un cotnacto o de una cuenta
                    $list_contact_type = ListContactType::where('value', $this->payload['to'])
                        ->orWhere('value', str_replace('+', '00', $this->payload['to']))
                        ->orWhere('value', substr($this->payload['to'], 1))
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

                    $current_call->from = $this->payload['from'];
                    $current_call->to = $this->payload['to'];
                    if(empty($current_call->account_id)) {
                        $current_call->account_id = $account_id;
                    }
                    if(empty($current_call->contact_id)) {
                        $current_call->contact_id = $contact_id;
                    }
                    $current_call->save();

                    //RC: Guardamos un registro en el log
                    $data_log['current_call_id'] = $current_call->id;
                    $data_log['call_log_type_id'] = 11;
                    $data_log['description'] = 'Modificamos la información: '.$this->payload['from'].' -> '.$this->payload['to'];
                    $data_log['start'] = strtotime($this->payload['start']);
                    CurrentCallLog::create($data_log);

                    //RC: Emitimos el evento de update call
                    $call_stat['id'] = $current_call->id;
                    $call_stat['from'] = $current_call->from;
                    $call_stat['to'] = $current_call->to;
                    $call_stat['start'] = $current_call->start;
                    $call_stat['duration'] = strtotime('now') - $current_call->start;
                    if($current_call->call_users()->whereNull('duration')->count() > 0) {
                        $current_call_user = $current_call->call_users()->whereNull('duration')->first();
                        if($current_call_user->user_id) {
                            $call_stat['user_id'] = $current_call_user->user_id;
                            $call_stat['user_name'] = $current_call_user->user->name;

                            if(!empty($user)) {
                                broadcast(new UpdateUserStatus($current_call_user->user));
                            }
                        }
                        $call_stat['department_id'] = $current_call->department_id;;
                        $call_stat['extension'] = $current_call_user->extension;
                    } else {
                        $call_stat['user_id'] = null;
                        $call_stat['user_name'] = null;
                        $call_stat['department_id'] = $current_call->department_id;;
                        $call_stat['extension'] = null;
                    }
                    $call_stat['queue'] = null;
                    $call_stat['call_type_id'] = $current_call->call_type_id;
                    $call_stat['call_status_id'] = $current_call->call_status_id;

                    broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));
                }
            }
        }
    }
}
