<?php

namespace App\Http\Controllers;

use App\Call;
use App\CampaignAnswer;
use App\CampaignAnswerEnd;
use App\CampaignAnswerJson;
use App\CampaignContact;
use App\CampaignFormInput;
use App\CurrentCall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignAnswersController extends Controller
{
    private $related_properties = ['campaign_answer_json', 'campaign_contact', 'campaign_form', 'campaign', 'campaign_answer_end'];

    public function total_answers_by_answer_end_id(Request $request)
    {
        if(!empty($request->company_id)) {
            $company_id = $request->company_id;
        } else {
            $user = get_loged_user();
            $company_id = $user->company_id;
        }
        $from = 'campaign_answers';

        $where = '';

        if (!empty($request->start)) {
            $where .= 'campaign_answers.created_at >= "' . $request->start . ' 00:00:00"';
        } else {
            $where .= 'campaign_answers.created_at >= "' . date('Y-m') . '-1 00:00:00"';
        }

        if (!empty($request->end)) {
            $where .= ' AND campaign_answers.created_at <= "' . $request->end . ' 23:59:59"';
        } else {
            $where .= ' AND campaign_answers.created_at <= "' . date('Y-m-d') . ' 23:59:59"';
        }

        if (!empty($request->campaigns_id)) {
            $campaigns_id = '';
            foreach ($request->campaigns_id as $campaign_id) {
                if (!empty($campaigns_id)) {
                    $campaign_id .= ',';
                }

                $campaigns_id .= $campaign_id;
            }

            $where .= ' AND campaign_answers.campaign_id IN (' . $campaigns_id . ')';
        }

        $select = "COUNT(*) AS total, campaign_answer_end_id";


        $sql = "SELECT $select FROM $from WHERE $where GROUP BY campaign_answer_end_id ORDER BY campaign_answer_end_id";

        $campaign_answer_ends = CampaignAnswerEnd::join('campaign_campaign_answer_end', 'campaign_campaign_answer_end.campaign_answer_end_id', '=', 'campaign_answer_ends.id')
        ->where('company_id', $company_id)
            ->orderBy('name', 'asc')
            ->select('campaign_answer_ends.*')
            ->get();

        $campaign_answers[0]['name'] = 'Sin final';
        $campaign_answers[0]['total'] = 0;
        foreach ($campaign_answer_ends as $campaign_answer_end) {
            $campaign_answers[$campaign_answer_end->id]['name'] = $campaign_answer_end->name;
            $campaign_answers[$campaign_answer_end->id]['total'] = 0;
        }

        $registers = DB::select($sql);

        foreach ($registers as $register) {
            if (empty($register->campaign_answer_end_id)) {
                $campaign_answers[0]['total'] = $register->total;
            } else {
                if (!isset($campaign_answers[$register->campaign_answer_end_id]['total'])) {
                    $campaign_answer_end = CampaignAnswerEnd::where('id', $register->campaign_answer_end_id)->first();
                    $campaign_answers[$campaign_answer_end->id]['name'] = $campaign_answer_end->name;
                    $campaign_answers[$campaign_answer_end->id]['total'] = 0;
                }

                $campaign_answers[$register->campaign_answer_end_id]['total'] = $register->total;
            }
        }

        return $campaign_answers;
    }

    public function api_search(Request $request, $page = 0)
    {
        $user = get_loged_user();


        $campaign_answers = CampaignAnswer::join('campaign_answer_jsons', 'campaign_answer_jsons.campaign_answer_id', '=', 'campaign_answers.id')
            ->select('campaign_answers.*');

        if (!empty($request->campaign_id)) {
            $campaign_answers->where('campaign_id', $request->campaign_id);
        }

        if (!empty($request->campaign_contact_id)) {
            $campaign_answers->where('campaign_contact_id', $request->campaign_contact_id);
        }

        if (!empty($request->fields)) {
            foreach ($request->fields as $field) {
                $key = 'campaign_answer_jsons.fields->' . $field['name'];
                $campaign_answers->where($key, 'like', '%' . $field['value'] . '%');
            }
        }

        $limit = $request->limit;
        $limit_start = ($page - 1) * $limit;

        if (!empty($request->sortColumn)) {
            $sortColumn = $request->sortColumn;
        } else {
            $sortColumn = 'created_at';
        }
        if ($request->sortDirection == -1) {
            $sortDirection = 'desc';
        } else {
            $sortDirection = 'asc';
        }

        $json['page'] = (int)$page;
        $json['limit'] = $limit;
        $json['limit_start'] = $limit_start;
        $json['total'] = $campaign_answers->count('campaign_answers.id');
        $json['total_pages'] = ceil($json['total'] / $limit);
        $json['data'] = $campaign_answers
            ->orderBy($sortColumn, $sortDirection)
            ->limit($limit)
            ->offset($limit_start)
            ->get()
            ->load($this->related_properties);

        return $json;
    }

    public function get($id)
    {
        return CampaignAnswer::findOrFail($id)->load($this->related_properties);
    }

    public function store(Request $request)
    {
        $data = $request->all();

        $contact = self::saveContact($data);
        $answer = self::saveAnswer($data, $contact->id);
        $answerJson = self::saveAnswerJson($data, $answer);

        return $answer->load($this->related_properties);
    }

    public function api_import(Request $request, int $campaignId)
    {
        //$this->module = get_user_module_security($this->module_key);
        if (true) {
            if (!empty($request->str_answers) && !empty($request->campaign_form_id)) {
                $campaignAnswers = explode("\n", $request->str_answers);

                $isHeader = true;
                $headers = [];
                foreach ($campaignAnswers as $cA) {
                    $campaignAnswer = explode("\t", $cA);
                    if (!$isHeader) {
                        unset($data);
                        if (count($campaignAnswer) > 0) {
                            $data = self::getDataFromImportRow($headers, $campaignAnswer);
                            $data['is_correct'] = true;
                            $data['campaign_id'] = $campaignId;
                            $data['campaign_form_id'] = $request->campaign_form_id;

                            $contact = self::saveContact($data);
                            $answer = self::saveAnswer($data, $contact->id);
                            $answerJson = self::saveAnswerJson($data, $answer);
                        }
                    } else {
                        $isHeader = false;

                        $headers = self::getHeadersColumns($campaignAnswer, $request->campaign_form_id);

                        if (empty($headers)) {
                            return ['errors' => 2];
                        }
                    }
                }

                return ['errors' => 0];
            }
        }

        return ['errors' => 1];
    }

    public function api_get_campaign_contact_answers($campaignId, $campaignContactId)
    {
        return CampaignAnswer::where('campaign_id', $campaignId)
            ->where('campaign_contact_id', $campaignContactId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->load($this->related_properties);
    }

    private function getHeadersColumns($cols, $campaignFormId)
    {
        $headers = [];
        $i = 0;
        foreach ($cols as $col) {
            $campaignFormInput = CampaignFormInput::where('campaign_form_id', $campaignFormId)
                ->where('label', trim($col))
                ->first();

            print_r($campaignFormInput);
            if (!empty($campaignFormInput)) {
                $headers[$i] = $campaignFormInput->name;
            } else {
                return [];
            }
            $i++;
        }

        return $headers;
    }

    private function getDataFromImportRow($headers, $cols)
    {
        $data = [];
        $i = 0;
        foreach ($cols as $col) {
            $data[$headers[$i]] = $col;
            $i++;
        }

        return $data;
    }

    private function saveContact($data)
    {
        if (!empty($data['campaign_contact_id'])) {
            $contact = CampaignContact::where('id', $data['campaign_contact_id'])->first();
        }

        if (empty($contact)) {
            $contact = new CampaignContact();
            $contact->campaign_id = $data['campaign_id'];
            $contact->save();
        }

        $contact->update($data);

        if (!empty($data['phone_1'])) {
            CurrentCall::where('campaign_id', $data['campaign_id'])
            ->where('from', $data['phone_1'])
                ->whereNull('campaign_contact_id')
                ->update(['campaign_contact_id' => $contact->id]);

            CurrentCall::where('campaign_id', $data['campaign_id'])
            ->where('to', $data['phone_1'])
                ->whereNull('campaign_contact_id')
                ->update(['campaign_contact_id' => $contact->id]);

            Call::where('campaign_id', $data['campaign_id'])
            ->where('from', $data['phone_1'])
                ->whereNull('campaign_contact_id')
                ->update(['campaign_contact_id' => $contact->id]);

            Call::where('campaign_id', $data['campaign_id'])
            ->where('to', $data['phone_1'])
                ->whereNull('campaign_contact_id')
                ->update(['campaign_contact_id' => $contact->id]);
        }

        if (!empty($data['phone_2'])) {
            CurrentCall::where('campaign_id', $data['campaign_id'])
            ->where('from', $data['phone_2'])
                ->whereNull('campaign_contact_id')
                ->update(['campaign_contact_id' => $contact->id]);

            CurrentCall::where('campaign_id', $data['campaign_id'])
            ->where('to', $data['phone_2'])
                ->whereNull('campaign_contact_id')
                ->update(['campaign_contact_id' => $contact->id]);

            Call::where('campaign_id', $data['campaign_id'])
            ->where('from', $data['phone_2'])
                ->whereNull('campaign_contact_id')
                ->update(['campaign_contact_id' => $contact->id]);

            Call::where('campaign_id', $data['campaign_id'])
            ->where('to', $data['phone_2'])
                ->whereNull('campaign_contact_id')
                ->update(['campaign_contact_id' => $contact->id]);
        }

        return $contact;
    }

    private function saveAnswer($data, $contactId)
    {
        if (!empty($data['id'])) {
            $answer = CampaignAnswer::where('id', $data['id'])->first();
        }

        if (empty($answer)) {
            $answer = new CampaignAnswer();
        }

        $answer->campaign_id = $data['campaign_id'];
        $answer->campaign_form_id = $data['campaign_form_id'];
        $answer->campaign_contact_id = $contactId;
        if (!empty($data['campaign_answer_end_id'])) {
            $answer->campaign_answer_end_id = $data['campaign_answer_end_id'];
        } else {
            $answer->campaign_answer_end_id = null;
        }
        $answer->is_correct = $data['is_correct'];
        $answer->save();

        return $answer;
    }

    private function saveAnswerJson($data, $answer)
    {
        $answerJson = CampaignAnswerJson::where('campaign_answer_id', $answer->id)
            ->first();

        if (empty($answerJson)) {
            $answerJson = new CampaignAnswerJson();
            $answerJson->campaign_answer_id = $answer->id;
        }

        $answerJson->fields = json_encode($data);
        $answerJson->save();

        return $answerJson;
    }
}
