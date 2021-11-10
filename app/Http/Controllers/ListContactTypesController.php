<?php

namespace App\Http\Controllers;

use App\Account;
use App\ListContactType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListContactTypesController extends Controller
{
    public function api_search(Request $request) {
        $user = get_loged_user();
        $filter = $request->filter;
        

        return DB::select("(SELECT DISTINCT 'accounts' as module_key, accounts.id as id, accounts.name, account_contact_types.value as value 
        FROM accounts 
        LEFT JOIN account_contact_types ON account_contact_types.account_id = accounts.id 
        LEFT JOIN tag_module ON tag_module.reference_id = accounts.id AND tag_module.module_key = 8
        LEFT JOIN tags ON tags.id = tag_module.tag_id 
        WHERE 
        accounts.company_id = 1 AND 
        account_contact_types.deleted_at IS NULL AND 
        accounts.deleted_at IS NULL AND (
        accounts.name LIKE '%".$filter."%' 
        OR accounts.corporate_name LIKE '%".$filter."%' 
        OR account_contact_types.value LIKE '%".$filter."%'
        OR tags.name LIKE '%".$filter."%'))
        UNION
        (SELECT DISTINCT 'contacts' as module_key, contacts.id as id, contacts.name, contact_contact_types.value as value 
        FROM contacts 
        LEFT JOIN contact_contact_types ON contact_contact_types.contact_id = contacts.id 
        LEFT JOIN tag_module ON tag_module.reference_id = contacts.id AND tag_module.module_key = 6
        LEFT JOIN tags ON tags.id = tag_module.tag_id 
        WHERE 
        contacts.company_id = 1 AND 
        contact_contact_types.deleted_at IS NULL AND 
        contacts.deleted_at IS NULL AND (
        contacts.name LIKE '%".$filter."%' 
        OR contact_contact_types.value LIKE '%".$filter."%'
        OR tags.name LIKE '%".$filter."%' 
        OR contacts.birthday = '".date('Y-m-d', strtotime("$filter"))."'))");

        return ListContactType::where('company_id', $user->company_id)
            ->where(function($query) use($filter) {
                $query->orWhere('name', 'like', '%'.$filter.'%')
                ->orWhere('value', 'like', '%'.$filter.'%');
            })
            ->get();
    }

    public function api_search_to_add_contact(Request $request) {
        $user = get_loged_user();
        $filter = $request->filter;

        return DB::select('
        SELECT "account" as type, accounts.id, CONCAT_WS("", accounts.code, " ", accounts.name) as name FROM accounts WHERE company_id=' . $user->company_id . ' AND deleted_at is null AND (code like "%' . $filter . '%" OR name like "%' . $filter . '%")
        ');
    }

    public function api_search_phone(Request $request) {
        $user = get_loged_user();
        $filter = $request->filter;
        $contacts = Account::join('account_contact_types', 'account_contact_types.account_id', '=', 'accounts.id')
        ->where('accounts.company_id', $user->company_id)
            ->where('account_contact_types.contact_type_id', 1)
            ->whereNull('account_contact_types.deleted_at')
            ->where(function($query) use($filter) {
            $query->orWhere('accounts.name', 'like', '%' . $filter . '%')
                ->orWhere('accounts.code', 'like', '%' . $filter . '%')
                ->orWhere('accounts.corporate_name', 'like', '%' . $filter . '%')
                ->orWhere('account_contact_types.name', 'like', '%' . $filter . '%')
                ->orWhere('account_contact_types.value', 'like', '%' . $filter . '%');
            })
            ->selectRaw('account_contact_types.id, CONCAT_WS("", accounts.code, " ", accounts.name, " ", account_contact_types.name) as name, REPLACE(CONCAT("0", account_contact_types.value)," ", "") as value, CONCAT_WS("", accounts.name, " ",account_contact_types.name, " ", account_contact_types.value) as searchable')
            ->limit(100)
            ->get();

        if (!empty($filter)) {
            $contacts[] =[
                'id' => 0,
                'name' => 'Llamar al '.$filter, 
                'value' => $filter,
                'searchable' => $filter,
            ];
        } 

        return $contacts;
    }
}
