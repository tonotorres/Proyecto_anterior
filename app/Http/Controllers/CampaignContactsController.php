<?php

namespace App\Http\Controllers;

use App\CampaignContact;
use Illuminate\Http\Request;

class CampaignContactsController extends Controller
{
    private $module_key = 58;
    private $related_properties = [];

    private $module;

    public function api_search(Request $request, $page = 0)
    {
        $user = get_loged_user();


        $campaign_contacts = CampaignContact::select('campaign_contacts.*');

        if (!empty($request->campaign_id)) {
            $campaign_contacts->where('campaign_contacts.campaign_id', $request->campaign_id);
        }

        if (!empty($request->name)) {
            $campaign_contacts->where('campaign_contacts.name', 'like', '%' . $request->name . '%');
        }

        if (!empty($request->last_name)) {
            $campaign_contacts->where('campaign_contacts.name', 'like', '%' . $request->last_name . '%');
        }

        if (!empty($request->birthday)) {
            $campaign_contacts->where('campaign_contacts.birthday', $request->birthday);
        }

        if (!empty($request->phone_1)) {
            $campaign_contacts->where('campaign_contacts.phone_1', 'like', '%' . $request->phone_1 . '%');
        }

        if (!empty($request->phone_2)) {
            $campaign_contacts->where('campaign_contacts.phone_2', 'like', '%' . $request->phone_2 . '%');
        }

        if (!empty($request->email_1)) {
            $campaign_contacts->where('campaign_contacts.email_1', 'like', '%' . $request->email_1 . '%');
        }

        if (!empty($request->email_2)) {
            $campaign_contacts->where('campaign_contacts.email_2', 'like', '%' . $request->email_2 . '%');
        }

        if (!empty($request->postal_code)) {
            $campaign_contacts->where('campaign_contacts.postal_code', 'like', '%' . $request->postal_code . '%');
        }

        if (!empty($request->location)) {
            $campaign_contacts->where('campaign_contacts.location', 'like', '%' . $request->location . '%');
        }

        if (!empty($request->region)) {
            $campaign_contacts->where('campaign_contacts.region', 'like', '%' . $request->region . '%');
        }

        if (!empty($request->country)) {
            $campaign_contacts->where('campaign_contacts.country', 'like', '%' . $request->country . '%');
        }


        $limit = $request->limit;
        $limit_start = ($page - 1) * $limit;

        if (!empty($request->sortColumn)) {
            $sortColumn = $request->sortColumn;
        } else {
            $sortColumn = 'name';
        }
        if ($request->sortDirection == -1) {
            $sortDirection = 'desc';
        } else {
            $sortDirection = 'asc';
        }

        $json['page'] = (int)$page;
        $json['limit'] = $limit;
        $json['limit_start'] = $limit_start;
        $json['total'] = $campaign_contacts->count('campaign_contacts.id');
        $json['total_pages'] = ceil($json['total'] / $limit);
        $json['data'] = $campaign_contacts
            ->orderBy($sortColumn, $sortDirection)
            ->limit($limit)
            ->offset($limit_start)
            ->get()
            ->load($this->related_properties);

        return $json;
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las cuentas activos
     * @return CampaignContact[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all($campaign_contact_list_id = null)
    {
        $this->module = get_user_module_security($this->module_key);
        $campaign_contacts = self::get_all();
        if (!empty($campaign_contacts)) {
            return $campaign_contacts;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_list()
    {

        return CampaignContact::select('id', 'name as label')
        ->orderBy('label', 'ASC')
        ->get();
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la incontactación de la cuenta
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        $campaign_contact = self::get($id);
        if (!empty($campaign_contact)) {
            return $campaign_contact;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la incontactación es correcta generamos una nueva cuenta, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->create)) {
            $campaign_contact = self::create($request->all());

            if (empty($campaign_contact['errors'])) {
                return $campaign_contact->load($this->related_properties);
            } else {
                return $campaign_contact;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la incontactación es correcta actualizamos la cuenta identificada por el parámetro $id, con la incontactación facilitada
     * @param Request $request
     * @param int $id Identificador de la cuenta
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->update)) {
            $campaign_contact = self::update($request->all(), $id);

            if (empty($campaign_contact['errors'])) {
                return $campaign_contact->load($this->related_properties);
            } else {
                return $campaign_contact;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_partial_update(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->update)) {
            $campaign_contact = self::partial_update($request->all(), $id);

            if (empty($campaign_contact['errors'])) {
                return $campaign_contact->load($this->related_properties);
            } else {
                return $campaign_contact;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Eliminamos la cuenta identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador de la cuenta
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function api_delete(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->delete)) {
            return self::delete($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_import(Request $request, int $campaignId)
    {
        $this->module = get_user_module_security($this->module_key);
        if (!empty($this->module->create)) {
            if (!empty($request->str_contacts)) {
                $campaignContacts = explode("\n", $request->str_contacts);

                $isHeader = true;
                foreach ($campaignContacts as $cC) {
                    if (!$isHeader) {
                        unset($data);

                        $campaignContact = explode("\t", $cC);
                        if (count($campaignContact) <= 14) {
                            $data = self::getDataFromImportRow($campaignContact);
                            $data['campaign_id'] = $campaignId;

                            $return = self::create($data);
                        }
                    } else {
                        $isHeader = false;
                    }
                }
            }
        }
    }

    private function getDataFromImportRow($row)
    {
        $data = [];
        if (!empty($row[0])) {
            $data['name'] = $row[0];
        }
        if (!empty($row[1])) {
            $data['last_name'] = $row[1];
        }
        if (!empty($row[2])) {
            $data['birthday'] = date('Y-m-d', strtotime($row[2]));
        }
        if (!empty($row[3])) {
            $data['nif'] = $row[3];
        }
        if (!empty($row[4])) {
            $data['phone_1'] = $row[4];
        }
        if (!empty($row[5])) {
            $data['phone_2'] = $row[5];
        }
        if (!empty($row[6])) {
            $data['email_1'] = $row[6];
        }
        if (!empty($row[7])) {
            $data['email_2'] = $row[7];
        }
        if (!empty($row[8])) {
            $data['address'] = $row[8];
        }
        if (!empty($row[9])) {
            $data['address_aux'] = $row[9];
        }
        if (!empty($row[10])) {
            $data['postal_code'] = $row[10];
        }
        if (!empty($row[11])) {
            $data['location'] = $row[11];
        }
        if (!empty($row[12])) {
            $data['region'] = $row[12];
        }
        if (!empty($row[13])) {
            $data['country'] = $row[13];
        }

        return $data;
    }

    private function get_all()
    {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        $user = get_loged_user();
        if (!empty($this->module->read)) {
            $campaign_contact = CampaignContact::where('company_id', $user->company_id);

            return $campaign_contact->get()->load($this->related_properties);
        } else if (!empty($this->module->own)) {
            $campaign_contact = CampaignContact::where('company_id', $user->company_id);

            return $campaign_contact->get()->load($this->related_properties);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    private function get($id)
    {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        //RC: Miramos si tenemos permisos para leer el objecto
        if (!empty($this->module->read) || !empty($this->module->own)) {
            $user = get_loged_user();
            $campaign_contact = CampaignContact::findOrFail($id);

            if ($campaign_contact->company_id != $user->company_id) {
                return null;
            } else {
                //RC: Si es de la misma compañía lo podemos devolver, en caso contrario no lo 
                return $campaign_contact->load($this->related_properties);
            }
        } else {
            return null;
        }

        if (!empty($this->module->read)) {
            return CampaignContact::findOrFail($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Valida y genera un nuevo registro de la cuenta con los datos facilitados en $data
     * @param array $data Campos a introducir
     * @return array Devuelve el objeto generado o un array con los errores de validación
     */
    private function create(array $data)
    {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id);

        $data = self::validate_default_values($data);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $user = get_loged_user();
            $data['company_id'] = $user->company_id;

            $campaign_contact = CampaignContact::create($data);

            return $campaign_contact;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro de la cuenta indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador de la cuenta
     * @return array Devuelve el objeto actualizado o un array con los errores de validación.
     */
    private function update(array $data, int $id)
    {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id, $id);

        $data = self::validate_default_values($data);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.s
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $campaign_contact = CampaignContact::findOrFail($id);
            $user = get_loged_user();
            if ($user->company_id == $campaign_contact->campaign->company_id) {
                $campaign_contact->update($data);
            }

            return $campaign_contact;
        }
    }

    private function partial_update(array $data, int $id)
    {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_partial_validations($this->module->user_template_id, $this->module->module_id, $data, $id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $campaign_contact = CampaignContact::findOrFail($id);
            $user = get_loged_user();
            if ($user->company_id == $campaign_contact->company_id) {
                $campaign_contact->update($data);
            }

            return $campaign_contact;
        }
    }

    private function validate_default_values($data)
    {
        $user = get_loged_user();

        return $data;
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador de la cuenta
     * @return CampaignContact
     */
    private function delete(int $id)
    {
        $campaign_contact = CampaignContact::findOrFail($id);
        $user = get_loged_user();
        if ($user->company_id == $campaign_contact->company_id) {
            $campaign_contact->delete();
        }

        return $campaign_contact;
    }
}
