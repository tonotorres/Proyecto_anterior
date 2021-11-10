<?php

namespace App\Http\Controllers;

use App\CampaignUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampaignUrlsController extends Controller
{
    public function api_store(Request $request)
    {
        $campaign_url = self::create($request->all());

        if (empty($campaign_call['errors'])) {
            return $campaign_url;
        } else {
            return $campaign_url;
        }
    }

    public function update_active($id, $active)
    {
        $campaign_url = CampaignUrl::findOrFail($active);
        $campaign_url->active = $active;
        $campaign_url->save();

        return $campaign_url;
    }

    public function api_delete($id)
    {
        $campaign_url = CampaignUrl::findOrFail($id);
        $campaign_url->delete();

        return $campaign_url;
    }

    private function create($data)
    {
        if (!empty($data['campaign_id'])) {
            $data['token'] = Str::random(32);
            $data['url'] = env('APP_URL') . '/external/campaigns/' . $data['token'];
            $campaign_url = CampaignUrl::create($data);

            return $campaign_url;
        } else {
            return ['errors' => true];
        }
    }
}
