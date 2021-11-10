<?php

namespace App\Http\Controllers;

use App\MessageTemplate;
use Illuminate\Http\Request;

class MessageTemplatesController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 40;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * Devuelve un array con la información necesaria para un listado
     * @param Request $request
     * @return Department[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_list(Request $request) {
        $this->module = get_user_module_security($this->module_key);
        $user = get_loged_user();
        if(!empty($this->module->read)) {
            return MessageTemplate::where('company_id', $user->company_id)
                ->select('id', 'name as label')
                ->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_all(Request $request) {
        $this->module = get_user_module_security($this->module_key);
        $user = get_loged_user();
        if(!empty($this->module->read)) {
            return MessageTemplate::where('company_id', $user->company_id)
                ->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }
}
