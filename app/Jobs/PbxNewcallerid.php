<?php

namespace App\Jobs;

use App\Campaign;
use App\CampaignCall;
use App\CampaignContact;
use App\CurrentCall;
use App\Events\CallUpdate;
use App\RouteOut;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PbxNewcallerid implements ShouldQueue
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
        if (!empty($this->data['Linkedid'])) {
            //RC: Obtenemos la llamada
            if ($this->data['company_id'] == -1) {
                $current_call = CurrentCall::where('linkedid', $this->data['Linkedid'])
                    ->first();

                if (!empty($current_call)) {
                    $this->data['company_id'] = $current_call->company_id;
                }
            } else {
                $current_call = CurrentCall::where('linkedid', $this->data['Linkedid'])
                    ->where('company_id', $this->data['company_id'])
                    ->first();
            }

            if ($current_call->call_type_id >= 2) {
            $current_call->from = $this->data['ConnectedLineNum'];
            $current_call->save();

            $route_out = RouteOut::where(
                'number',
                $this->data['ConnectedLineNum']
            )
            ->where('company_id', $this->data['company_id'])
            ->first();

            if (!empty($route_out)) {
                $current_call->route_out_id = $route_out->id;

                if ($current_call->to != 's') {
                    $campaign = Campaign::join('campaign_out_calls', 'campaign_out_calls.campaign_id', '=', 'campaigns.id')
                    ->where('company_id', $current_call->company_id)
                    ->where(
                        'campaigns.active',
                        '1'
                    )
                    ->whereNull('campaigns.finished_at')
                    ->where('campaign_out_calls.route_out_id', $route_out->id)
                    ->where('campaign_out_calls.start',
                        '<=',
                        date('Y-m-d')
                    )
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
                            ->where('phone', substr($current_call->to, 1));
                        })->first();

                        if (!empty($campaignCall)) {
                            $campaignCall->ringing_user_id = null;
                            $campaignCall->is_correct = 1;
                            $campaignCall->save();
                        }
                    }
                }
            }
            }
            $current_call->save();

            //RC: Obtenemos el usuario activo
            $user = $current_call->call_users()->where('duration', 'null')->orderBy('id', 'DESC')->first();

            //RC: Obtenemos el número de la extensión
            $number = get_channel_name($this->data['Channel']);

            //RC: Emitimos el evento de update call
            $call_stat['id'] = $current_call->id;
            $call_stat['from'] = $current_call->from;
            $call_stat['to'] = $current_call->to;
            $call_stat['start'] = $current_call->start;
            $call_stat['duration'] = strtotime('now') - $current_call->start;
            if (!empty($user)) {
                $call_stat['user_id'] = $user->id;
                $call_stat['user_name'] = $user->name;
                $call_stat['extension'] = $number;
                if (!empty($user->department_id)) {
                    $call_stat['department_id'] = $user->department_id;
                    $current_call->department_id = $user->department_id;
                    $current_call->save();
                } else {
                    $call_stat['department_id'] = null;
                }
            } else {
                $call_stat['user_id'] = null;
                $call_stat['department_id'] = $current_call->department_id;;
                $call_stat['user_name'] = $number;
                $call_stat['extension'] = $number;
            }
            $call_stat['queue'] = $current_call->queue;
            $call_stat['call_type_id'] = $current_call->call_type_id;
            $call_stat['call_status_id'] = $current_call->call_status_id;
        

            broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));
        }
    }
}
