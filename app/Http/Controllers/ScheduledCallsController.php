<?php

namespace App\Http\Controllers;

use App\Call;
use App\Campaign;
use App\CurrentCall;
use App\ScheduledCall;
use Illuminate\Http\Request;

class ScheduledCallsController extends Controller
{
    public function getCampaignScheduledCalls($campaign_id)
    {
        return ScheduledCall::where('campaign_id', $campaign_id)
            ->whereNull('notified_at')
            ->get()->load(['user']);
    }

    public function createFromOtherCall(Request $request)
    {
        $user = get_loged_user();
        $call = $request->call;

        $data['company_id'] = $call['company_id'];
        $data['user_id'] = $user->id;
        $data['call_date'] = $request->call_date;

        if (!empty($call['campaign_id'])) {
            $data['campaign_id'] = $call['campaign_id'];
            if (!empty($call['campaign_contact_id']) && !empty($call['campaign_contact']['name'])) {
                $data['subject'] = $call['campaign_contact']['name'] . ' de ' . $call['campaign']['name'];
            } else {
                $data['subject'] = $call['campaign']['name'];
            }

            if ($call['call_type_id'] == 1) {
                $campaign = Campaign::where('id', $call['campaign_id'])->first();
                if (!empty($campaign->campaign_out_call->route_out->prefix)) {
                    $data['phone'] = $campaign->campaign_out_call->route_out->prefix . $call['from'];
                } else {
                    $data['phone'] = $call['from'];
                }
            } else {
                $data['phone'] = $call['to'];
            }
        } else {
            if ($call['call_type_id'] == 1) {
                $data['phone'] = $call['from'];
            } else {
                $data['phone'] = $call['to'];
            }

            if (!empty($call['account_id'])) {
                $data['subject'] = $call['account']['name'] . ' ' . $data['phone'];
            } else {
                $data['subject'] = $data['phone'];
            }
        }

        $scheduledCall = ScheduledCall::create($data);

        return $scheduledCall;
    }

    public function api_store(Request $request)
    {
        $user = get_loged_user();
        $data = $request->all();
        $data['company_id'] = $user->company_id;
        return ScheduledCall::create($data);
    }

    public function api_update(Request $request, $id)
    {
        $scheduledCall = ScheduledCall::findOrFail($id);
        $scheduledCall->update($request->all());

        return $scheduledCall;
    }

    public function postponeScheduledCall($id)
    {
        $scheduledCall = ScheduledCall::findOrFail($id);

        $scheduledCall->call_date = date('Y-m-d H:i:s', strtotime('+5 minute', strtotime($scheduledCall->call_date)));
        $scheduledCall->notified_at = null;
        $scheduledCall->save();
    }
}
