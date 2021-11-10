<?php

namespace App\Jobs;

use App\Campaign;
use App\CampaignContact;
use App\CurrentCall;
use App\CurrentCallQueue;
use App\Events\CallUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PbxQueueJoin implements ShouldQueue
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
        //RC: Miramos si tenemos la llamada
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

        if (!empty($current_call)) {
            $current_call->queue = $this->data['Exten'];
            $current_call->save();

            //RC: Generamos un registro de cola
            $current_call_queue = new CurrentCallQueue();
            $current_call_queue->current_call_id = $current_call->id;
            $current_call_queue->queue = $this->data['Exten'];
            $current_call_queue->start = $this->data['start'];
            $current_call_queue->save();

            //RC: Miramos si tenemos que enlazar una campaña
            $campaign = Campaign::join('campaign_in_calls', 'campaign_in_calls.campaign_id', '=', 'campaigns.id')
            ->where('company_id', $current_call->company_id)
                ->where('campaigns.active', '1')
                ->whereNull('campaigns.finished_at')
                ->where('campaign_in_calls.queue', $this->data['Exten'])
                ->where('campaign_in_calls.start', '<=', date('Y-m-d'))
                ->where('campaign_in_calls.end', '>=', date('Y-m-d'))
                ->select('campaigns.*')
                ->first();

            if (!empty($campaign)) {
                $current_call->campaign_id = $campaign->id;

                $campaign_contact = CampaignContact::where('campaign_id', $campaign->id)
                    ->where(function ($query) use ($current_call) {
                        $query->where('phone_1', $current_call->from)
                            ->orWhere('phone_2', $current_call->from);
                    })
                    ->orderBy('id', 'DESC')
                    ->first();

                if (empty($campaign_contact)) {
                    $campaign_contact = new CampaignContact();
                    $campaign_contact->create([
                        'campaign_id' => $campaign->id,
                        'phone_1' => $current_call->from
                    ]);
                }

                $current_call->campaign_contact_id = $campaign_contact->id;

                $current_call->save();
            }

            //RC: Emitimos el evento de update call
            $call_stat['id'] = $current_call->id;
            $call_stat['from'] = $current_call->from;
            $call_stat['to'] = $current_call->to;
            $call_stat['start'] = $current_call->start;
            $call_stat['duration'] = strtotime('now') - $current_call->start;
            $call_stat['user_id'] = null;
            $call_stat['user_name'] = null;
            $call_stat['department_id'] = $current_call->department_id;;
            $call_stat['extension'] = null;
            $call_stat['queue'] = $current_call->queue;
            $call_stat['call_type_id'] = $current_call->call_type_id;
            $call_stat['call_status_id'] = $current_call->call_status_id;

            broadcast(new CallUpdate($call_stat, $current_call, $current_call->company_id));
        }
    }
}
