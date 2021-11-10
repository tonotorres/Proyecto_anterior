<?php

namespace App\Jobs;

use App\CurrentCall;
use App\CurrentCallIvr;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PbxNewExten implements ShouldQueue
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
        if (!empty($this->data['Context']) && strpos($this->data['Context'], 'ivr-') !== FALSE && !empty($this->data['Application']) && $this->data['Application'] == 'Goto') {

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
                $data_save['current_call_id'] = $current_call->id;
                $data_save['pbx_ivr'] = str_replace('ivr-', '', $this->data['Context']);;
                $data_save['option'] = $this->data['Extension'];
                $data_save['start'] = $this->data['start'];
                CurrentCallIvr::create($data_save);
            }
        } elseif (!empty($this->data['Context']) && $this->data['Context'] == 'macro-speeddial-lookup') {
            if (!empty($this->data['Application']) && $this->data['Application'] == 'Set') {
                if (!empty($this->data['AppData'])) {
                    $number = str_replace('SPEEDDIALNUMBER=', '', $this->data['AppData']);

                    if (!empty($number)) {

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
                            $current_call->to = $number;
                            $account_contact_type = get_account_type_by_number($current_call->company_id, $current_call->to);

                            if (!empty($account_contact_type)) {
                                $current_call->account_id = $account_contact_type->account_id;
                            }

                            $current_call->save();
                        }
                    }
                }
            }
        }
    }
}
