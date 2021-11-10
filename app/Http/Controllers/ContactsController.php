<?php

namespace App\Http\Controllers;

use App\Contact;
use App\ListContactType;
use App\UserTemplateModule;
use Illuminate\Http\Request;
use PhpParser\Node\Expr\AssignOp\Concat;

class ContactsController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 6;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * Devuelve un array con el id y el nombre en el campo label de los contactos
     * @return [id, label]
     */
    public function api_get_list()
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->read)) {
            $user = get_loged_user();
            return Contact::where('company_id', $user->company_id)
                ->select('id', 'name as label')
                ->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los contactos activos
     * @return App\Contact[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all()
    {
        $this->module = get_user_module_security($this->module_key);
        $contacts = self::get_all();
        if (!empty($contacts)) {
            return $contacts;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información del contacto
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        $contact = self::get($id);
        if (!empty($contact)) {
            return $contact;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos un nuevo contacto, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request)
    {
        $this->module = get_user_module_security($this->module_key);
        if (!empty($this->module->create)) {
            $contact = self::create($request->all());

            if (empty($contact['errors'])) {
                return $contact->load('emails', 'phones', 'account', 'tags');
            } else {
                return $contact;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos el contacto identificado por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id)
    {
        $this->module = get_user_module_security($this->module_key);

        //RC: Validamos si tenemos permisos para modificar
        if (!empty($this->module->update)) {
            $original_contact = self::get($id);
            $user = get_loged_user();

            //RC: Validamos si el contacto es de la misma empresa
            if ($original_contact->company_id == $user->company_id) {
                $contact = self::update($request->all(), $id);

                //RC: Si no tenemos errores devolvemos el objecto, si tenemos errores devolvemos los errores
                if (empty($contact['errors'])) {
                    return $contact->load('emails', 'phones', 'account', 'tags');
                } else {
                    return $contact;
                }
            } else {
                return response()->json(['error' => 'unauthenticated'], 401);
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Eliminamos el contacto identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador del tipo de cuenta
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
    
    /**
     * @author Roger Corominas
     * Devuelve un array con todos las cuentas si tenemos permiso
     * @return App\Account[]
     */
    private function get_all()
    {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        if (!empty($this->module->read)) {
            $user = get_loged_user();
            return Contact::where('company_id', $user->company_id)->get()->load('emails', 'phones', 'account', 'tags');
        } else {
            return [];
        }
    }

    /**
     * @author Roger Corominas  
     * Devuelve el contacto seleccionado siempre que tengamos permiso
     * @param  Integer $id
     * @return App\Contact
     */
    private function get(int $id)
    {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        //RC: Miramos si tenemos permisos para leer el objecto
        if (!empty($this->module->read)) {
            $user = get_loged_user();
            $contact = Contact::findOrFail($id)->load('emails', 'phones', 'account', 'tags');

            //RC: Si es de la misma compañía lo podemos devolver, en caso contrario no lo 
            if ($contact->company_id == $user->company_id) {
                return $contact;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y genera un nuevo registro del contacto con los datos facilitados en $data
     * @param array $data Campos a introducir
     * @return array Devuelve el objeto generado o un array con los errores de validación
     */
    private function create(array $data)
    {
        $user = get_loged_user();
        
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: seleccionamos la empresa
            $data['company_id'] = $user->company_id;
            
            //RC: si la validación fue correcta tenemos que generar el objeto
            $contact = Contact::create($data);

            if(!empty($data['tags_id'])) {
                $tags = [];
                foreach($data['tags_id'] as $tag) {
                    $tags[$tag] = ['module_key' => $this->module_key];
                }

                $contact->tags()->sync($tags);
            }

            return $contact;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro del contacto indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador del contacto
     * @return array Devuelve el objeto actualizado o un array con los errores de validación.
     */
    private function update(array $data, int $id)
    {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id, $id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $contact = Contact::findOrFail($id);
            $contact->update($data);

            //RC: generamos el registro en la tabla de lista
            ListContactType::where('module_key', '7')
                ->where('reference_id', $contact->id)
                ->update(['name' => $contact->name]);

            if(!empty($data['tags_id'])) {
                $tags = [];
                foreach($data['tags_id'] as $tag) {
                    $tags[$tag] = ['module_key' => $this->module_key];
                }

                $contact->tags()->sync($tags);
            } else {
                $contact->tags()->detach();
            }

            return $contact;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador del contacto
     * @return Contact
     */
    private function delete(int $id)
    {
        $contact = Contact::findOrFail($id);

        //RC: generamos el registro en la tabla de lista
        ListContactType::where('module_key', '7')
            ->where('reference_id', $contact->id)
            ->delete();

        $contact->emails()->delete();
        $contact->phones()->delete();
        $contact->delete();

        return $contact;
    }
}
