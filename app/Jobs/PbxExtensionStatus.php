<?php

namespace App\Jobs;

use App\Events\ExtensionStatus;
use App\Extension;
use App\ExtensionStatusLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PbxExtensionStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
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
        if ($this->data['company_id'] == -1) {
            $extension = Extension::where('number', $this->data['Exten'])
                ->first();
        } else {
            $extension = Extension::where('number', $this->data['Exten'])
                ->where('company_id', $this->data['company_id'])
                ->first();
        }

        if (!empty($extension)) {
            switch ($this->data['Status']) {
                case '-2':
                    $extension_status_id = 1;
                    break;
                case '-1':
                    $extension_status_id = 2;
                    break;
                case '0':
                    $extension_status_id = 3;
                    break;
                case '1':
                    $extension_status_id = 4;
                    break;
                case '2':
                    $extension_status_id = 5;
                    break;
                case '4':
                    $extension_status_id = 6;
                    break;
                case '8':
                    $extension_status_id = 7;
                    break;
                case '9':
                    $extension_status_id = 8;
                    break;
                case '16':
                    $extension_status_id = 9;
                    break;
                case '17':
                    $extension_status_id = 10;
                    break;
                default:
                    $extension_status_id = null;
                    break;
            }

            $extension->extension_status_id = $extension_status_id;
            $extension->save();

            $extension_status_log = new ExtensionStatusLog();
            $extension_status_log->extension_id = $extension->id;
            $extension_status_log->extension_status_id = $extension_status_id;
            $extension_status_log->save();

            broadcast(new ExtensionStatus($extension->load('extension_status'), $extension->company_id));
        }
    }
}
