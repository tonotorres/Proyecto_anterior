<?php

namespace App\Jobs;

use App\CurrentCall;
use App\Hook;
use App\Trunk;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PbxNewChannel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        if (!empty($this->data['Linkedid']) && $this->data['Channel'] != 'OutgoingSpoolFailed') {
            $current_call = CurrentCall::where('linkedid', $this->data['Linkedid'])
                ->first();
            $agent_connect = '';

            if (empty($current_call)) {
                //RC: Si en la extensión tenemos un asterisco no generamos la llamada
                if (strpos($this->data['Exten'], '*') === FALSE || substr($this->data['Exten'], 0, 2) == '*0') {
                    //RC: Si no tenemos una llamada con la tenemos que generar
                    $current_call = create_call_from_ami_event($this->data);
                    $call_event = 'EventsCallStart';

                    //RC: Procesamos los hooks para despues de marcar la llamada como saliente
                    $hooks = Hook::where('company_id', $current_call->company_id)
                        ->where('key', 'AFTER_SET_INBOUND_CALL')
                        ->where('active', 1)
                        ->orderBy('position', 'asc')
                        ->get();

                    foreach ($hooks as $hook) {
                        $namehook = 'callshooks/' . date('Y') . '/' . date('m') . '/' . date('d') . 'log.txt';
                        Storage::append($namehook, json_encode($hook));
                        try {
                            eval($hook->code);
                        } catch (Exception $e) {
                            $namehook = 'callshooks/' . date('Y') . '/' . date('m') . '/' . date('d') . '_error_log.txt';
                            Storage::append($namehook, $e->getMessage());
                        }
                    }
                }
            } else {
                //RC: Si la llamada es de tipo interna y el canal es un troncal tenemos que marcala como llamada de salida
                if ($current_call->call_type_id == 3) {
                    $trunk_name = get_channel_name($this->data['Channel']);

                    //RC: Miramos si tenemos algun troncal con este nombre
                    $trunk = Trunk::whereRaw("LOWER(trunks.name) like '" . strtolower($trunk_name) . "'")
                        ->first();

                    if (!empty($trunk)) {
                        $current_call->call_type_id = 2;
                        $current_call->save();

                        //RC: Procesamos los hooks para despues de marcar la llamada como saliente
                        $hooks = Hook::where('company_id', $current_call->company_id)
                        ->where('key', 'AFTER_SET_OUTBOUND_CALL')
                            ->where('active', 1)
                            ->orderBy('position', 'asc')
                            ->get();

                        foreach ($hooks as $hook) {
                            $namehook = 'callshooks/' . date('Y') . '/' . date('m') . '/' . date('d') . 'log.txt';
                            Storage::append($namehook, json_encode($hook));
                            try {
                                eval($hook->code);
                            } catch (Exception $e) {
                                $namehook = 'callshooks/' . date('Y') . '/' . date('m') . '/' . date('d') . '_error_log.txt';
                                Storage::append($namehook, $e->getMessage());
                            }
                        }
                    }
                }
                $call_event = 'CallUpdate';
            }

            if (!empty($current_call)) {
                //RC: generamos el canal
                $pbx_channel = creat_pbx_channel_from_ami_event($this->data, $current_call->id);

                //RC: Procesamos los hooks para despues de un nuevo canal
                $hooks = Hook::where('company_id', $current_call->company_id)
                ->where('key', 'AFTER_CREATE_NEW_CHANNEL')
                    ->where('active', 1)
                    ->orderBy('position', 'asc')
                    ->get();

                foreach ($hooks as $hook) {
                    $namehook = 'callshooks/' . date('Y') . '/' . date('m') . '/' . date('d') . 'log.txt';
                    Storage::append($namehook, json_encode($hook));
                    try {
                        eval($hook->code);
                    } catch (Exception $e) {
                        $namehook = 'callshooks/' . date('Y') . '/' . date('m') . '/' . date('d') . '_error_log.txt';
                        Storage::append($namehook, $e->getMessage());
                    }
                }
                

                //RC: generamos el evento
                generate_event_call_start($current_call, $call_event, $agent_connect);
            }
        }
    }
}
