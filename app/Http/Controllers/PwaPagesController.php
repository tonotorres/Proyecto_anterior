<?php

namespace App\Http\Controllers;

use App\Account;
use App\PwaPage;
use App\SeAccountToken;
use App\UserTemplateModule;
use Illuminate\Http\Request;

class PwaPagesController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 10;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * PwaPagesController constructor.
     * Asignamos el objeto módulo al atributo module.
     */
    public function __construct() {
        $user_template_id = 1;
        $this->module = UserTemplateModule::generateQueryModuleByUserTempalateModuleKey($user_template_id, $this->module_key)
            ->first();
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las páginas activos
     * @param Request $request
     * @return PwaPage[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all(Request $request) {
        if(!empty($this->module->read)) {
            return PwaPage::whereNotIn('url', ['header', 'footer'])->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_list(Request $request) {
        if(!empty($this->module->read)) {
            return PwaPage::select('id', 'name as label')->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información de la página
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id) {
        if(!empty($this->module->read)) {
            $pwa_page = PwaPage::findOrFail($id)->load('pwa_elements', 'pwa_elements.pwa_element_type', 'pwa_elements.pwa_language');
            $i = 0;

            foreach($pwa_page->pwa_elements as $pwa_element) {
                $pwa_page->pwa_elements[$i]->content = json_decode($pwa_element->content);
                $i++;
            }

            return $pwa_page;
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información de la página
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get_by_url(string $url) {
        if(!empty($this->module->read)) {
            $pwa_page = PwaPage::where('url', $url)->first();
            if(!empty($pwa_page)) {
                $pwa_page->load('pwa_elements', 'pwa_elements.pwa_element_type', 'pwa_elements.pwa_language');
                $i = 0;

                foreach ($pwa_page->pwa_elements as $pwa_element) {
                    $pwa_page->pwa_elements[$i]->content = json_decode($pwa_element->content);
                    $i++;
                }

                return $pwa_page;
            } else {
                return [];
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos una nueva página, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request) {
        if(!empty($this->module->create)) {
            $pwa_page = self::create($request->all());

            if(empty($pwa_page['errors'])) {
                return $pwa_page;
            } else {
                return $pwa_page;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos la página identificada por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador de la página
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id) {
        if(!empty($this->module->update)) {
            $pwa_page = self::update($request->all(), $id);

            if(empty($pwa_page['errors'])) {
                return $pwa_page;
            } else {
                return $pwa_page;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Eliminamos la página identificada por el campo Id
     * @param Request $request
     * @param int $id Identificador de la página
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function api_delete(Request $request, int $id) {
        if(!empty($this->module->delete)) {
            return self::delete($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function front() {
        return view('pwa_pages.front');
    }

    public function api_front_login(Request $request) {
        if(!empty($request->user) && !empty($request->password)) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://ews01.salvadorescoda.com/api/accounts/login",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => array('username' => 'conecta', 'password' => 'escoda00')
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $json = json_decode($response);
            if (!empty($json->token)) {
                $curl2 = curl_init();
                curl_setopt_array($curl2, array(
                    CURLOPT_URL => "https://ews01.salvadorescoda.com/api/execute/q_login_user",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => array('token' => $json->token, 'accountid' => '4', 'user' => $request->user, 'password' => $request->password)
                ));
                $response2 = curl_exec($curl2);
                curl_close($curl2);
                $json2 = json_decode($response2);
                if ($json2->data[0]->pv_ret == 'S') {
                    //RC: save token register
                    $token_id = self::save_se_token($json2->data[0]->pv_token, $json2->data[0]->pv_numero, $json2->data[0]->pv_nombre);
                    $return['error'] = 0;
                    $return['token'] = $json2->data[0]->pv_token;
                    $return['code'] = $json2->data[0]->pv_numero;
                    $return['name'] = $json2->data[0]->pv_nombre;
                    $return['token_id'] = $token_id;
                } else {
                    $return['error'] = 1;
                    $return['pv_ret'] = $json2->data[0]->pv_ret;
                    $return['token'] = $json2->data[0]->pv_token;
                    $return['code'] = $json2->data[0]->pv_numero;
                    $return['name'] = $json2->data[0]->pv_nombre;
                }

            } else {
                $return['error'] = 2;
            }
        } else {
            $return['error'] = 3;
        }

        return $return;

    }

    private function save_se_token($token, $code, $name) {
        $account = Account::where('code', $code)->first();
        if(empty($account)) {
            $account = new Account();
            $account->company_id = 1;
            $account->account_type_id = 1;
            $account->code = $code;
        }

        $account->name = $name;
        $account->corporate_name = $name;
        $account->save();

        $token = SeAccountToken::create([
            'account_id' => $account->id,
            'token' => $token
        ]);

        return $token->id;
    }

    public function api_front_get_recovery_token(Request $request) {
        if(!empty($request->code) && (!empty($request->phone) || !empty($request->email))) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://ews01.salvadorescoda.com/api/accounts/login",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => array('username' => 'conecta', 'password' => 'escoda00')
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $json = json_decode($response);
            if (!empty($json->token)) {
                $curl2 = curl_init();
                curl_setopt_array($curl2, array(
                    CURLOPT_URL => "https://ews01.salvadorescoda.com/api/execute/u_recordar_password",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => array('token' => $json->token, 'accountid' => '4', 'pv_num_cuenta' => $request->code, 'pv_num_movil' => $request->phone, 'pv_mail' => $request->email)
                ));
                $response2 = curl_exec($curl2);
                curl_close($curl2);
                $json2 = json_decode($response2);
                if ($json2->data[0]->pv_ret == 'S') {
                    $return['error'] = 0;
                    $return['token'] = $json2->data[0]->pv_new_token;
                } else {
                    $return['error'] = 1;
                    $return['pv_ret'] = $json2->data[0]->pv_ret;
                    $return['token'] = $json2->data[0]->pv_new_token;
                }

            } else {
                $return['error'] = 2;
            }
        } else {
            $return['error'] = 3;
        }

        return $return;

    }

    public function api_front_set_recovery_password(Request $request) {
        if (!empty($request->token) && !empty($request->password) && !empty($request->repassword) && $request->password == $request->repassword) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://ews01.salvadorescoda.com/api/accounts/login",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => array('username' => 'conecta', 'password' => 'escoda00')
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $json = json_decode($response);
            if (!empty($json->token)) {
                $curl2 = curl_init();
                curl_setopt_array($curl2, array(
                    CURLOPT_URL => "https://ews01.salvadorescoda.com/api/execute/u_modif_recuerda_password",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => array('token' => $json->token, 'accountid' => '4', 'pv_token_recovery' => $request->token, 'pv_new_password' => $request->password)
                ));
                $response2 = curl_exec($curl2);
                curl_close($curl2);
                $json2 = json_decode($response2);
                if ($json2->data[0]->pv_ret == 'S') {
                    $return['error'] = 0;
                    $return['msg'] = 'Password modificado correctamente';
                } else {
                    $return['error'] = 1;
                    $return['msg'] = 'El enlace ya no es correcto';
                }
            } else {
                $return['error'] = 2;
                $return['msg'] = 'El enlace ya no es correcto';
            }
        } else {
            $return['error'] = 3;
            $return['msg'] = 'El enlace ya no es correcto';
        }

        return $return;
    }

    public function api_front_change_password(Request $request) {
        if (!empty($request->token) && !empty($request->password) && !empty($request->oldpassword)) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://ews01.salvadorescoda.com/api/accounts/login",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => array('username' => 'conecta', 'password' => 'escoda00')
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $json = json_decode($response);
            if (!empty($json->token)) {
                $curl2 = curl_init();
                curl_setopt_array($curl2, array(
                    CURLOPT_URL => "https://ews01.salvadorescoda.com/api/execute/u_modifica_password",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => array('token' => $json->token, 'accountid' => '4', 'pv_token' => $request->token, 'password_new' => $request->password, 'password_old' => $request->oldpassword)
                ));
                $response2 = curl_exec($curl2);
                curl_close($curl2);
                $json2 = json_decode($response2);
                if ($json2->data[0]->pv_ret == 'S') {
                    $return['error'] = 0;
                    $return['msg'] = 'Contraseña modificada correctamente';
                    $return['token'] = $json2->data[0]->pv_new_token;
                } else {
                    $return['error'] = 1;
                    $return['msg'] = 'No se pudo modifica la contraseña';
                    $return['token'] = '';
                }
            } else {
                $return['error'] = 2;
                $return['msg'] = 'No se pudo modifica la contraseña';
                $return['token'] = '';
            }
        } else {
            $return['error'] = 3;
            $return['msg'] = 'No se pudo modifica la contraseña';
            $return['token'] = '';
        }

        return $return;
    }

    /**
     * @author Roger Corominas
     * Valida y genera un nuevo registro de la página con los datos facilitados en $data
     * @param array $data Campos a introducir
     * @return array Devuelve el objeto generado o un array con los errores de validación
     */
    private function create (Array $data) {
        //RC: Si no tenemos empresa, añadimos la empresa por defecto
        if(empty($data['company_id'])) {
            $data['company_id'] = 1;
        }

        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            return PwaPage::create($data);
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro de la página indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador de la página
     * @return array Devuelve el objeto actualizado o un array con los errores de validación.
     */
    private function update (Array $data, int $id) {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id, $id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            //RC: si la validación fue correcta tenemos que generar el objeto
            $pwa_page = PwaPage::findOrFail($id);
            $pwa_page->update($data);

            return $pwa_page;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador de la página
     * @return PwaPage
     */
    private function delete (int $id) {
        $pwa_page = PwaPage::findOrFail($id);
        $pwa_page->delete();

        return $pwa_page;
    }
}
