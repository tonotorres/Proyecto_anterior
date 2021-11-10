<?php

namespace App\Console\Commands;

use App\Account;
use App\AccountContactType;
use App\AddressBookDestination;
use App\ListContactType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importaccounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importamos los contactos al sistema actual';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $csvFile = storage_path('/app/LISTIN.csv');
        $file_handle = fopen($csvFile, 'r');
        while (!feof($file_handle)) {
            $line_of_text = fgetcsv($file_handle, 0, ',');

            dd($line_of_text);

            $account = Account::where('name', 'like', utf8_encode($line_of_text[0]))
                ->first();

            if (empty($account)) {
                $this->line("<info>Generamos el contacto:</info>  " . utf8_encode($line_of_text[0]));
                //RC: Si no tenemos cuenta lo generamos
                $account = new Account();
                $account->company_id = 1;
                $account->name = utf8_encode($line_of_text[0]);
                $account->save();
            }

            if (!empty($line_of_text[3])) {
                if ($account->phones()->where('value', utf8_encode($line_of_text[3]))->count() === 0) {
                    $this->line("<info>Generamos el teléfono:</info>  " . utf8_encode($line_of_text[3]));
                    $account_contact_type = new AccountContactType();
                    $account_contact_type->account_id = $account->id;
                    $account_contact_type->contact_type_id = 1;
                    if (!empty(utf8_encode($line_of_text[1]))) {
                        $account_contact_type->name = utf8_encode($line_of_text[1]);
                    }
                    $account_contact_type->value = utf8_encode($line_of_text[3]);
                    $account_contact_type->save();

                    $list_contact_type = new ListContactType();
                    $list_contact_type->company_id = $account_contact_type->account->company_id;
                    $list_contact_type->module_key = 9;
                    $list_contact_type->contact_type_id = $account_contact_type->contact_type_id;
                    if(!empty($account_contact_type->account->code)) {
                        $list_contact_type->name = $account_contact_type->account->code.' '.$account_contact_type->account->name;
                    } else {
                        $list_contact_type->name = $account_contact_type->account->name;
                    }
                    $list_contact_type->value = $account_contact_type->value;
                    $list_contact_type->reference_type_id = $account_contact_type->id;
                    $list_contact_type->reference_id = $account_contact_type->account_id;
                    $list_contact_type->save();
                }
            }

            if (!empty($line_of_text[4])) {
                if ($account->phones()->where('value', utf8_encode($line_of_text[4]))->count() === 0) {
                    $this->line("<info>Generamos el teléfono:</info>  " . utf8_encode($line_of_text[4]));
                    $account_contact_type = new AccountContactType();
                    $account_contact_type->account_id = $account->id;
                    $account_contact_type->contact_type_id = 1;
                    if (!empty(utf8_encode($line_of_text[1]))) {
                        $account_contact_type->name = utf8_encode($line_of_text[1]);
                    }
                    $account_contact_type->value = utf8_encode($line_of_text[4]);
                    $account_contact_type->save();

                    $list_contact_type = new ListContactType();
                    $list_contact_type->company_id = $account_contact_type->account->company_id;
                    $list_contact_type->module_key = 9;
                    $list_contact_type->contact_type_id = $account_contact_type->contact_type_id;
                    if (!empty($account_contact_type->account->code)) {
                        $list_contact_type->name = $account_contact_type->account->code . ' ' . $account_contact_type->account->name;
                    } else {
                        $list_contact_type->name = $account_contact_type->account->name;
                    }
                    $list_contact_type->value = $account_contact_type->value;
                    $list_contact_type->reference_type_id = $account_contact_type->id;
                    $list_contact_type->reference_id = $account_contact_type->account_id;
                    $list_contact_type->save();
                }
            }
            
        }
        fclose($file_handle);
    }
}
