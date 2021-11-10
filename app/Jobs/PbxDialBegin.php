<?php

namespace App\Jobs;

use App\Campaign;
use App\CampaignCall;
use App\CampaignContact;
use App\CampaignOutCall;
use App\CurrentCall;
use App\CurrentCallUserCalled;
use App\Extension;
use App\Hook;
use App\PbxChannel;
use App\PbxChannelState;
use App\RouteOut;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PbxDialBegin implements ShouldQueue
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
        if (!empty($this->data['Channel'])) {

            $pbx_channel = PbxChannel::where('name', $this->data['Channel'])->first();

            if (empty($pbx_channel)) {
                $pbx_channel = creat_pbx_channel_from_ami_event($this->data, null);
            }

            //RC: Si no tenemos canal lo tenemos que generar

            if (!empty($pbx_channel)) {

                //RC: Guaradamos el estado
                $pbx_channel_state = PbxChannelState::where('key', $this->data['ChannelState'])->first();

                if (empty($pbx_channel_state)) {
                    $pbx_channel_state = new PbxChannelState();
                    $pbx_channel_state->key = $this->data['ChannelState'];
                    $pbx_channel_state->name = $this->data['ChannelStateDesc'];
                    $pbx_channel_state->save();
                }

                $pbx_channel->pbx_channel_state_id = $pbx_channel_state->id;

                //RC: Obtenemos el estado
                $current_call = getCurrentCallByLinkedid($this->data['Linkedid'], $this->data['company_id']);

                if (!empty($current_call)) {
                    $pbx_channel->current_call_id = $current_call->id;

                    //RC: Miramos si el destino es una extensión para margar la llamada
                    if (!empty($this->data['DestChannel'])) {
                        $name = get_channel_name($this->data['DestChannel']);

                        $extension = Extension::where('number', $name)
                            ->where('company_id', $current_call->company_id)
                            ->first();

                        if (!empty($extension)) {
                            //RC: Si tenemos una extensión la tenemos que marcar como llamante
                            $user = get_extension_user($current_call->company_id, $extension->number);

                            if (!empty($user)) {
                                $user_id = $user->id;
                                $department_id = $user->department_id;
                            } else {
                                $user_id = null;
                                $department_id = null;
                            }

                            $current_call_user_called = CurrentCallUserCalled::create([
                                'current_call_id' => $current_call->id,
                                'user_id' => $user_id,
                                'department_id' => $department_id,
                                'extension' => $extension->number,
                                'start' => $this->data['start'],
                                'answered' => 0,

                            ]);

                            if (!empty($department_id)) {
                                $current_call->department_id = $department_id;
                                $current_call->save();
                            }
                        }
                    }

                    if ($this->data['DestCallerIDNum'] != 's' && $current_call->call_type_id >= 2 && ($current_call->to == 's' || empty($current_call->to))) {
                        $current_call->to = $this->data['DestCallerIDNum'];
                        $current_call->call_type_id = 2;

                        //RC: Miramos de que ruta de entrada viene
                        $route_out = RouteOut::where('number', $this->data['DestCallerIDNum'])->first();
                        if (!empty($route_out)) {
                            $current_call->route_out_id = $route_out->id;
                        }

                        if (empty($current_call->account_id)) {

                            $account_contact_type = get_account_type_by_number($current_call->company_id, $current_call->to);

                            if (!empty($account_contact_type)) {
                                $current_call->account_id = $account_contact_type->account_id;
                            }
                        }

                        if (!empty($current_call->route_out_id)) {
                            $route_out = RouteOut::where('id', $current_call->route_out_id)->first();
                        }

                        //RC: miramos si tenemos que asignar a una campaña
                        if (!empty($route_out)) {
                            $campaign = Campaign::join('campaign_out_calls', 'campaign_out_calls.campaign_id', '=', 'campaigns.id')
                            ->where('company_id', $current_call->company_id)
                                ->where('campaigns.active', '1')
                                ->whereNull('campaigns.finished_at')
                                ->where('campaign_out_calls.route_out_id', $route_out->id)
                                ->where('campaign_out_calls.start', '<=', date('Y-m-d'))
                                ->where('campaign_out_calls.end', '>=', date('Y-m-d'))
                                ->select('campaigns.*')
                                ->first();

                            if (!empty($campaign)) {
                                $current_call->campaign_id = $campaign->id;

                                $campaign_contact = CampaignContact::where('campaign_id', $campaign->id)
                                ->where(function ($query) use ($current_call) {
                                    $query->where('phone_1', $current_call->to)
                                    ->orWhere('phone_2', $current_call->to)
                                    ->orWhere('phone_1', substr($current_call->to, 1))
                                        ->orWhere('phone_2', substr($current_call->to, 1));
                                })
                                    ->orderBy('id', 'DESC')
                                    ->first();

                                if (empty($campaign_contact)) {
                                    $campaign_contact = new CampaignContact();
                                    $campaign_contact->create([
                                        'campaign_id' => $campaign->id,
                                        'phone_1' => $current_call->to
                                    ]);
                                }

                                if (!empty($campaign_contact)) {
                                    $current_call->campaign_contact_id = $campaign_contact->id;
                                }

                                //RC: Miramos si tenemos alguna llamada saliente cargada
                                $campaignCall = CampaignCall::where('campaign_id', $campaign->id)
                                ->whereNotNull('ringing_user_id')
                                    ->where(function ($query) use ($current_call) {
                                        $query->where('phone', $current_call->to)
                                    ->orWhere('phone', substr($current_call->to, 1));
                                    })->first();

                                if (!empty($campaignCall)) {
                                    $campaignCall->ringing_user_id = null;
                                    $campaignCall->is_correct = 1;
                                    $campaignCall->save();
                                }
                            }
                        }
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

                        $call_event = 'CallUpdate';
                        generate_event_call_start($current_call, $call_event, 'external_call');
                    }
                }


                $pbx_channel->save();
            }
        }
    }
}
