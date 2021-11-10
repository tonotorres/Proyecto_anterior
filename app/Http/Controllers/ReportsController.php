<?php

namespace App\Http\Controllers;

use App\BreakTime;
use App\CallEnd;
use App\DelayedReport;
use App\Jobs\CallListReport;
use App\Report;
use App\RouteIn;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 36;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * Devuelve un array con todas las cuentas activos
     * @return Report[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
     */
    public function api_get_all() {
        $this->module = get_user_module_security($this->module_key);
        $reports = self::get_all();
        if (!empty($reports)) {
            return $reports->load('report_items', 'report_items.report_type');
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    public function api_get_list()
    {
        $this->module = get_user_module_security($this->module_key);

        if (!empty($this->module->list)) {
            $user = get_loged_user();
            return Report::select('id', 'name as label')
                ->where('company_id', $user->company_id)
                ->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la información de la cuenta
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_get(int $id) {
        $this->module = get_user_module_security($this->module_key);

        $report = self::get($id);
        if (!empty($report)) {
            return $report->load('report_items', 'report_items.report_type');
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta generamos una nueva cuenta, sino devolvemos los errores
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_store(Request $request) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->create)) {
            $report = self::create($request->all());

            if(empty($report['errors'])) {
                return $report->load('report_items', 'report_items.report_type');
            } else {
                return $report;
            }
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    /**
     * @author Roger Corominas
     * Si la información es correcta actualizamos la cuenta identificada por el parámetro $id, con la información facilitada
     * @param Request $request
     * @param int $id Identificador de la cuenta
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function api_update(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->update)) {
            $report = self::update($request->all(), $id);

            if(empty($report['errors'])) {
                return $report->load('report_items', 'report_items.report_type');
            } else {
                return $report;
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
    public function api_delete(Request $request, int $id) {
        $this->module = get_user_module_security($this->module_key);

        if(!empty($this->module->delete)) {
            return self::delete($id);
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    private function get_all() {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        if(!empty($this->module->read)) {
            $user = get_loged_user();
            return Report::where('company_id', $user->company_id)->get();
        } else {
            return response()->json(['error' => 'unauthenticated'], 401);
        }
    }

    private function get($id) {
        //RC: Si no tenemos la seguridad del módulo lo volvemos a generar
        if (empty($this->module)) {
            $this->module = get_user_module_security($this->module_key);
        }

        //RC: Miramos si tenemos permisos para leer el objecto
        if (!empty($this->module->read)) {
            $user = get_loged_user();
            $report = Report::findOrFail($id);
            if($user->company_id == $report->company_id) {
                return $report;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y genera un nuevo registro de la cuenta con los datos facilitados en $data
     * @param array $data Campos a introducir
     * @return array Devuelve el objeto generado o un array con los errores de validación
     */
    private function create (Array $data) {
        //RC: Obtenemos las validaciones
        $validations = get_user_template_fields_validations($this->module->user_template_id, $this->module->module_id);

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            $user = get_loged_user();
            $data['company_id'] = $user->company_id;
            //RC: si la validación fue correcta tenemos que generar el objeto
            $report = Report::create($data);

            return $report;
        }
    }

    /**
     * @author Roger Corominas
     * Valida y actualiza el registro de la cuenta indentificado por $id con los datos del Array $data
     * @param array $data Campos a modificar
     * @param int $id Identificador de la cuenta
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
            $user = get_loged_user();
            $report = Report::findOrFail($id);
            
            if($user->company_id == $report->company_id) {
                $report->update($data);
            }

            return $report;
        }
    }

    /**
     * @author Roger Corominas
     * Elimina el objeto
     * @param int $id Identificador de la cuenta
     * @return Report
     */
    private function delete (int $id) {
        $user = get_loged_user();
        $report = Report::findOrFail($id);
        
        if($user->company_id == $report->company_id) {
            $report->delete();
        }

        return $report;
    }

    public function report_call_list(Request $request) {
        $user = get_loged_user();
        $company_id = $user->company_id;

        if(!empty($request->start)) {
            $start = $request->start;
        } else {
            $start = date('Y-m-1');
        }

        if(!empty($request->end)) {
            $end = $request->end;
        } else {
            $end = date('Y-m-d');
        }

        if(!empty($request->department_id)) {
            $department_id = $request->department_id;
        } else {
            $department_id = null;
        }

        if(!empty($request->user_id)) {
            $user_id = $request->user_id;
        } else {
            $user_id = null;
        }

        if(!empty($request->delimeter)) {
            $delimeter = $request->delimeter;
        } else {
            $delimeter = ';';
        }

        $data = $request->all();
        $data['company_id'] = $user->company_id;
        $data['email'] = $user->email;

        $delayed_report = new DelayedReport();
        $delayed_report->company_id = $user->company_id;
        $delayed_report->name = 'CallListReport';
        $delayed_report->data = json_encode($data);
        $delayed_report->finished = 0;
        $delayed_report->save();

        // CallListReport::dispatch($delimeter, $company_id, $start, $end, $department_id, $user_id);

        $json['email'] = $user->email;
        return $json;
    }

    public function report_call_ivr_by_ddi(Request $request) {
        $user = get_loged_user();

        if(!empty($request->start)) {
            $start = $request->start;
        } else {
            $start = date('Y-m-1');
        }

        if(!empty($request->end)) {
            $end = $request->end;
        } else {
            $end = date('Y-m-d');
        }

        if(!empty($request->departments)) {
            $departments = implode(', ', $request->departments);
        } else {
            $departments = null;
        }

        if(!empty($request->ddis)) {
            $ddis = implode(', ', $request->ddis);
        } else {
            $ddis = null;
        }

        if(!empty($request->tags)) {
            $tags = implode(', ', $request->tags);
        } else {
            $tags = null;
        }


        //RC: Ejecutamos las consultas
        $calls_by_ddis = DB::SELECT("
            SELECT COUNT(*) as total, SUM(duration) as duration, calls.call_status_id, route_ins.name as ddi
            FROM calls 
            INNER JOIN route_ins ON route_ins.id = calls.route_in_id 
            WHERE calls.company_id = ".$user->company_id." 
            AND calls.call_type_id = 1
            AND calls.start >= ".strtotime($start. '00:00:00')." 
            AND calls.start <= ".strtotime($end. '23:59:59')." 
            ".(!empty($departments) ? " AND calls.department_id IN (".$departments.")" : '')."
            " . (!empty($ddis) ? " AND calls.route_in_id IN (" . $ddis . ")" : '') . "
            GROUP BY route_ins.name,calls.call_status_id 
            ORDER BY route_ins.name,calls.call_status_id
        ");

        $json['calls_by_ddis'] = [];
        $index_route_ins = [];
        $ris = RouteIn::where('company_id', $user->company_id);
        if(!empty($departments)) {
            $ris->whereRaw("(department_id In (".$departments.") OR department_id is null)");
        }
        if(!empty($ddis)) {
            $ris->whereRaw("id In (".$ddis.")");
        }
            
        $route_ins = $ris->orderBy('name')
            ->get();

        $i = 0;
        foreach($route_ins as $route_in) {
            $json['calls_by_ddis'][$i]['ddi'] = $route_in->name;
            $json['calls_by_ddis'][$i]['success'] = 0;
            $json['calls_by_ddis'][$i]['failed'] = 0;
            $json['calls_by_ddis'][$i]['voicemail'] = 0;
            $json['calls_by_ddis'][$i]['total'] = 0;
            $json['calls_by_ddis'][$i]['duration'] = 0;

            $index_route_ins[$route_in->name] = $i;
            $i++;
        }

        $ddi_index = [];
        foreach($calls_by_ddis as $call_by_ddi) {
            if (!isset($index_route_ins[$call_by_ddi->ddi])) {
                $json['calls_by_ddis'][$i]['ddi'] = $call_by_ddi->ddi;
                $json['calls_by_ddis'][$i]['success'] = 0;
                $json['calls_by_ddis'][$i]['failed'] = 0;
                $json['calls_by_ddis'][$i]['voicemail'] = 0;
                $json['calls_by_ddis'][$i]['total'] = 0;
                $json['calls_by_ddis'][$i]['duration'] = 0;

                $index_route_ins[$call_by_ddi->ddi] = $i;
                $i++;
            }

            $index = $index_route_ins[$call_by_ddi->ddi];

            switch($call_by_ddi->call_status_id) {
                case 3:
                    $json['calls_by_ddis'][$index]['success'] += $call_by_ddi->total;
                break;
                case 4:
                    $json['calls_by_ddis'][$index]['failed'] += $call_by_ddi->total;
                break;
                case 5:
                    $json['calls_by_ddis'][$index]['voicemail'] += $call_by_ddi->total;
                break;

            }

            $json['calls_by_ddis'][$index]['total'] += $call_by_ddi->total;
            $json['calls_by_ddis'][$index]['duration'] += $call_by_ddi->duration;
        }


        return $json;
    }

    public function report_call_ivr_by_ddi_and_tag(Request $request) {
        $user = get_loged_user();

        if(!empty($request->start)) {
            $start = $request->start;
        } else {
            $start = date('Y-m-1');
        }

        if(!empty($request->end)) {
            $end = $request->end;
        } else {
            $end = date('Y-m-d');
        }

        if(!empty($request->departments)) {
            $departments = implode(', ', $request->departments);
        } else {
            $departments = null;
        }

        if(!empty($request->ddis)) {
            $ddis = implode(', ', $request->ddis);
        } else {
            $ddis = null;
        }

        if(!empty($request->tags)) {
            $tags = implode(', ', $request->tags);
        } else {
            $tags = null;
        }


        $calls_by_ddis_and_tags = DB::SELECT("
            SELECT COUNT(*) as total, SUM(duration) as duration, calls.to, calls.call_status_id, t1.name AS tag_ivr, t2.name AS tag_ivr_option, call_ivrs.option, call_ivrs.pbx_ivr, route_ins.name as ddi
            FROM calls 
            INNER JOIN call_ivrs ON calls.id = call_ivrs.call_id 
            INNER JOIN route_ins ON route_ins.id = calls.route_in_id  
            LEFT JOIN tags t1 ON t1.id = call_ivrs.ivr_tag_id
            LEFT JOIN tags t2 ON t2.id = call_ivrs.ivr_option_tag_id
            WHERE calls.company_id = ".$user->company_id." 
            AND calls.start >= ".strtotime($start. '00:00:00')." 
            AND calls.start <= ".strtotime($end. '23:59:59')." 
            ".(!empty($departments) ? " AND calls.department_id IN (".$departments.")" : '')."
            ".(!empty($ddis) ? " AND calls.route_in_id IN (".$ddis.")" : '')."
            ".(!empty($tags) ? " AND (call_ivrs.ivr_tag_id IN (".$tags.") OR call_ivrs.ivr_option_tag_id IN (".$tags."))" : '')."
            GROUP BY calls.to, route_ins.name, t1.name, t2.name, call_ivrs.pbx_ivr, call_ivrs.option, calls.call_status_id 
            ORDER BY calls.to, call_ivrs.pbx_ivr, t1.name, call_ivrs.option, t2.name, calls.call_status_id
        ");

        
        $json['calls_by_ddis_and_tags'] = [];

        $i = 0;
        $ddi_index = [];
        foreach($calls_by_ddis_and_tags as $calls_by_ddis_and_tag) {
            $ddi = $calls_by_ddis_and_tag->to;
            if(!empty($calls_by_ddis_and_tag->tag_ivr)) {
                $tag_ivr = $calls_by_ddis_and_tag->tag_ivr;
            } else {
                $tag_ivr = 0;
            }

            if(!empty($calls_by_ddis_and_tag->tag_ivr_option)) {
                $tag_ivr_option = $calls_by_ddis_and_tag->tag_ivr_option;
            } else {
                $tag_ivr_option = 0;
            }

            if(!isset($ddi_index[$ddi][$tag_ivr][$tag_ivr_option])) {
                $json['calls_by_ddis_and_tags'][$i]['ddi'] = $calls_by_ddis_and_tag->ddi;
                $json['calls_by_ddis_and_tags'][$i]['tag_ivr'] = $calls_by_ddis_and_tag->tag_ivr;
                $json['calls_by_ddis_and_tags'][$i]['tag_ivr_option'] = $calls_by_ddis_and_tag->tag_ivr_option;
                $json['calls_by_ddis_and_tags'][$i]['option'] = $calls_by_ddis_and_tag->option;
                $json['calls_by_ddis_and_tags'][$i]['success'] = 0;
                $json['calls_by_ddis_and_tags'][$i]['failed'] = 0;
                $json['calls_by_ddis_and_tags'][$i]['voicemail'] = 0;
                $json['calls_by_ddis_and_tags'][$i]['total'] = 0;
                $json['calls_by_ddis_and_tags'][$i]['duration'] = 0;

                $ddi_index[$ddi][$tag_ivr][$tag_ivr_option] = $i;
                $i++;
            }

            $index = $ddi_index[$ddi][$tag_ivr][$tag_ivr_option];

            switch($calls_by_ddis_and_tag->call_status_id) {
                case 3:
                    $json['calls_by_ddis_and_tags'][$index]['success'] += $calls_by_ddis_and_tag->total;
                break;
                case 4:
                    $json['calls_by_ddis_and_tags'][$index]['failed'] += $calls_by_ddis_and_tag->total;
                break;
                case 5:
                    $json['calls_by_ddis_and_tags'][$index]['voicemail'] += $calls_by_ddis_and_tag->total;
                break;    
            }

            $json['calls_by_ddis_and_tags'][$index]['total'] += $calls_by_ddis_and_tag->total;
            $json['calls_by_ddis_and_tags'][$index]['duration'] += $calls_by_ddis_and_tag->duration;
        }


        return $json;
    }

    public function report_call_ivr_by_tag(Request $request) {
        $user = get_loged_user();

        if(!empty($request->start)) {
            $start = $request->start;
        } else {
            $start = date('Y-m-1');
        }

        if(!empty($request->end)) {
            $end = $request->end;
        } else {
            $end = date('Y-m-d');
        }

        if(!empty($request->departments)) {
            $departments = implode(', ', $request->departments);
        } else {
            $departments = null;
        }

        if(!empty($request->ddis)) {
            $ddis = implode(', ', $request->ddis);
        } else {
            $ddis = null;
        }

        if(!empty($request->tags)) {
            $tags = implode(', ', $request->tags);
        } else {
            $tags = null;
        }

        
        $calls_by_tags = DB::SELECT("
            SELECT COUNT(*) as total, SUM(duration) as duration, calls.call_status_id, t1.name AS tag_ivr, t2.name AS tag_ivr_option, call_ivrs.option, call_ivrs.pbx_ivr
            FROM calls 
            INNER JOIN call_ivrs ON calls.id = call_ivrs.call_id 
            LEFT JOIN tags t1 ON t1.id = call_ivrs.ivr_tag_id
            LEFT JOIN tags t2 ON t2.id = call_ivrs.ivr_option_tag_id
            WHERE calls.company_id = ".$user->company_id." 
            AND calls.start >= ".strtotime($start. '00:00:00')." 
            AND calls.start <= ".strtotime($end. '23:59:59')." 
            ".(!empty($departments) ? " AND calls.department_id IN (".$departments.")" : '')."
            ".(!empty($ddis) ? " AND calls.route_in_id IN (".$ddis.")" : '')."
            ".(!empty($tags) ? " AND (call_ivrs.ivr_tag_id IN (".$tags.") OR call_ivrs.ivr_option_tag_id IN (".$tags."))" : '')."
            GROUP BY t1.name, t2.name, call_ivrs.pbx_ivr, call_ivrs.option, calls.call_status_id 
            ORDER BY call_ivrs.pbx_ivr, t1.name, call_ivrs.option, t2.name, calls.call_status_id
        ");

        $json['sql'] = "
        SELECT COUNT(*) as total, SUM(duration) as duration, calls.call_status_id, t1.name AS tag_ivr, t2.name AS tag_ivr_option, call_ivrs.option, call_ivrs.pbx_ivr
        FROM calls 
        INNER JOIN call_ivrs ON calls.id = call_ivrs.call_id 
        LEFT JOIN tags t1 ON t1.id = call_ivrs.ivr_tag_id
        LEFT JOIN tags t2 ON t2.id = call_ivrs.ivr_option_tag_id
        WHERE calls.company_id = ".$user->company_id." 
        AND calls.start >= ".strtotime($start. '00:00:00')." 
        AND calls.start <= ".strtotime($end. '23:59:59')." 
        ".(!empty($departments) ? " AND calls.department_id IN (".$departments.")" : '')."
        ".(!empty($ddis) ? " AND calls.route_in_id IN (".$ddis.")" : '')."
        ".(!empty($tags) ? " AND (call_ivrs.ivr_tag_id IN (".$tags.") OR call_ivrs.ivr_option_tag_id IN (".$tags."))" : '')."
        GROUP BY t1.name, t2.name, call_ivrs.pbx_ivr, call_ivrs.option, calls.call_status_id 
        ORDER BY call_ivrs.pbx_ivr, t1.name, call_ivrs.option, t2.name, calls.call_status_id
    ";
        $json['calls_by_tags'] = [];

        $i = 0;
        $ddi_index = [];
        foreach($calls_by_tags as $calls_by_tag) {
            if(!empty($calls_by_tag->tag_ivr)) {
                $tag_ivr = $calls_by_tag->tag_ivr;
            } else {
                $tag_ivr = 0;
            }

            if(!empty($calls_by_tag->tag_ivr_option)) {
                $tag_ivr_option = $calls_by_tag->tag_ivr_option;
            } else {
                $tag_ivr_option = 0;
            }

            if(!isset($ddi_index[$tag_ivr][$tag_ivr_option])) {
                $json['calls_by_tags'][$i]['tag_ivr'] = $calls_by_tag->tag_ivr;
                $json['calls_by_tags'][$i]['tag_ivr_option'] = $calls_by_tag->tag_ivr_option;
                $json['calls_by_tags'][$i]['option'] = $calls_by_tag->option;
                $json['calls_by_tags'][$i]['success'] = 0;
                $json['calls_by_tags'][$i]['failed'] = 0;
                $json['calls_by_tags'][$i]['voicemail'] = 0;
                $json['calls_by_tags'][$i]['total'] = 0;
                $json['calls_by_tags'][$i]['duration'] = 0;

                $ddi_index[$tag_ivr][$tag_ivr_option] = $i;
                $i++;
            }

            $index = $ddi_index[$tag_ivr][$tag_ivr_option];

            switch($calls_by_tag->call_status_id) {
                case 3:
                    $json['calls_by_tags'][$index]['success'] += $calls_by_tag->total;
                break;
                case 4:
                    $json['calls_by_tags'][$index]['failed'] += $calls_by_tag->total;
                break;
                case 5:
                    $json['calls_by_tags'][$index]['voicemail'] += $calls_by_tag->total;
                break;
            }

            $json['calls_by_tags'][$index]['total'] += $calls_by_tag->total;
            $json['calls_by_tags'][$index]['duration'] += $calls_by_tag->duration;
        }


        return $json;
    }

    public function total_and_duration_calls_by_call_type_id(Request $request) {
        $from = 'calls';

        if(!empty($request->company_id)) {
            $company_id = $request->company_id;
        } else {
            $user = get_loged_user();
            $company_id = $user->company_id;
        }

        $where = 'calls.company_id = '.$company_id; 

        if(!empty($request->start)) {
            $where .= ' AND calls.start >= '.strtotime($request->start.' 00:00:00');
        } else {
            $where .= ' AND calls.start >= '.strtotime(date('Y-m').'-1 00:00:00');
        }

        if(!empty($request->end)) {
            $where .= ' AND calls.start <= '.strtotime($request->end.' 23:59:59');
        } else {
            $where .= ' AND calls.start <= '.strtotime(date('Y-m-d').' 23:59:59');
        }

        if (!empty($request->campaigns_id)) {
            $campaigns_id = '';
            foreach ($request->campaigns_id as $campaign_id) {
                if (!empty($campaigns_id)) {
                    $campaign_id .= ',';
                }

                $campaigns_id .= $campaign_id;
            }

            $where .= ' AND calls.campaign_id IN (' . $campaigns_id . ')';
        }

        if(!empty($request->departments_id)) {
            $departments_id = '';
            foreach($request->departments_id as $department_id) {
                if(!empty($departments_id)) {
                    $departments_id .= ',';
                }

                $departments_id .= $department_id;
            }

            $where .= ' AND calls.department_id IN ('.$departments_id.')';
        }

        if(!empty($request->users_id)) {
            $users_id = '';
            foreach($request->users_id as $user_id) {
                if(!empty($users_id)) {
                    $users_id .= ',';
                }

                $users_id .= $user_id;
            }

            $where .= ' AND call_users.user_id IN ('.$users_id.')';
            $from .= ' INNER JOIN call_users ON call_users.call_id = calls.id';
            $select = "COUNT(*) AS total, SUM(call_users.duration) as total_duration, call_type_id";
        } else {
            $select = "COUNT(*) AS total, SUM(duration) as total_duration, call_type_id";
        }
        
        $sql = "SELECT $select FROM $from WHERE $where GROUP BY call_type_id ORDER BY call_type_id";

        $calls['in']['total'] = 0;
        $calls['in']['total_duration'] = 0;
        $calls['in']['media_duration'] = 0;
        $calls['out']['total'] = 0;
        $calls['out']['total_duration'] = 0;
        $calls['out']['media_duration'] = 0;
        $calls['internal']['total'] = 0;
        $calls['internal']['total_duration'] = 0;
        $calls['internal']['media_duration'] = 0;

        $registers = DB::select($sql);

        foreach($registers as $register) {
            switch($register->call_type_id) {
                case 1:
                    $calls['in']['total'] = $register->total;
                    $calls['in']['total_duration'] = $register->total_duration;
                    $calls['in']['media_duration'] = $register->total_duration / $register->total;
                break;
                case 2:
                    $calls['out']['total'] = $register->total;
                    $calls['out']['total_duration'] = $register->total_duration;
                    $calls['out']['media_duration'] = $register->total_duration / $register->total;
                break;
                case 3:
                    $calls['internal']['total'] = $register->total;
                    $calls['internal']['total_duration'] = $register->total_duration;
                    $calls['internal']['media_duration'] = $register->total_duration / $register->total;
                break;
            }
        }

        return $calls;
    }

    public function percentages_calls_by_call_end_and_user(Request $request)
    {
        if(!empty($request->company_id)) {
            $company_id = $request->company_id;
        } else {
            $user = get_loged_user();
            $company_id = $user->company_id;
        }
        $from = 'calls';

        $where = 'calls.company_id = ' . $company_id . " AND calls.call_status_id = 3 AND calls.call_type_id <> 3";

        if (!empty($request->start)) {
            $where .= ' AND calls.start >= ' . strtotime($request->start . ' 00:00:00');
        } else {
            $where .= ' AND calls.start >= ' . strtotime(date('Y-m') . '-1 00:00:00');
        }

        if (!empty($request->end)) {
            $where .= ' AND calls.start <= ' . strtotime($request->end . ' 23:59:59');
        } else {
            $where .= ' AND calls.start <= ' . strtotime(date('Y-m-d') . ' 23:59:59');
        }

        if (!empty($request->departments_id)) {
            $departments_id = '';
            foreach ($request->departments_id as $department_id) {
                if (!empty($departments_id)) {
                    $departments_id .= ',';
                }

                $departments_id .= $department_id;
            }

            $where .= ' AND calls.department_id IN (' . $departments_id . ')';
        }

        if (!empty($request->users_id)) {
            $users_id = '';
            foreach ($request->users_id as $user_id) {
                if (!empty($users_id)) {
                    $users_id .= ',';
                }

                $users_id .= $user_id;
            }

            $where .= ' AND call_users.user_id IN (' . $users_id . ')';
        }

        $from .= ' INNER JOIN call_users ON call_users.call_id = calls.id AND call_users.user_id IS NOT NULL';
        $select = "COUNT(*) AS total, SUM(call_users.duration) as total_duration, call_end_id, user_id";

        $sql = "SELECT $select FROM $from WHERE $where GROUP BY user_id, call_end_id ORDER BY user_id, call_end_id";

        $call_ends = CallEnd::where('company_id', $company_id)
            ->orderBy('name', 'asc')
            ->get();

        $registers = DB::select($sql);

        $last_user_id = 0;
        $calls = [];
        $call_end_index = [];
        $last_index_user = -1;
        foreach ($registers as $register) {
            if ($last_user_id != $register->user_id) {
                $last_index_user++;
                $last_user_id = $register->user_id;

                $user = User::where('id', $register->user_id)->first();

                $calls[$last_index_user]['id'] = $register->user_id;
                $calls[$last_index_user]['name'] = $user->name;
                $calls[$last_index_user]['total_calls'] = 0;
                $calls[$last_index_user]['total_duration'] = 0;
                $calls[$last_index_user]['calls'] = [];

                $calls[$last_index_user]['calls'][0]['name'] = 'Sin final';
                $calls[$last_index_user]['calls'][0]['total'] = 0;
                $calls[$last_index_user]['calls'][0]['total_duration'] = 0;

                $j = 1;
                foreach ($call_ends as $call_end) {
                    $calls[$last_index_user]['calls'][$j]['name'] = $call_end->name;
                    $calls[$last_index_user]['calls'][$j]['total'] = 0;
                    $calls[$last_index_user]['calls'][$j]['total_duration'] = 0;

                    $call_end_index[$call_end->id] = $j;
                    $j++;
                }
            }

            if (!empty($register->call_end_id)) {
                $calls[$last_index_user]['calls'][$call_end_index[$register->call_end_id]]['total'] = $register->total;
                $calls[$last_index_user]['calls'][$call_end_index[$register->call_end_id]]['total_duration'] = $register->total_duration;
            } else {
                $calls[$last_index_user]['calls'][0]['total'] = $register->total;
                $calls[$last_index_user]['calls'][0]['total_duration'] = $register->total_duration;
            }

            $calls[$last_index_user]['total_calls'] += $register->total;
            $calls[$last_index_user]['total_duration'] += $register->total_duration;
        }

        return $calls;
    }

    public function total_and_duration_calls_by_call_end_id(Request $request) {
        if(!empty($request->company_id)) {
            $company_id = $request->company_id;
        } else {
            $user = get_loged_user();
            $company_id = $user->company_id;
        }

        $from = 'calls';

        $where = 'calls.company_id = '.$company_id." AND calls.call_status_id = 3 AND calls.call_type_id <> 3"; 

        if(!empty($request->start)) {
            $where .= ' AND calls.start >= '.strtotime($request->start.' 00:00:00');
        } else {
            $where .= ' AND calls.start >= '.strtotime(date('Y-m').'-1 00:00:00');
        }

        if(!empty($request->end)) {
            $where .= ' AND calls.start <= '.strtotime($request->end.' 23:59:59');
        } else {
            $where .= ' AND calls.start <= '.strtotime(date('Y-m-d').' 23:59:59');
        }

        if (!empty($request->campaigns_id)) {
            $campaigns_id = '';
            foreach ($request->campaigns_id as $campaign_id) {
                if (!empty($campaigns_id)) {
                    $campaign_id .= ',';
                }

                $campaigns_id .= $campaign_id;
            }

            $where .= ' AND calls.campaign_id IN (' . $campaigns_id . ')';
        }

        if(!empty($request->departments_id)) {
            $departments_id = '';
            foreach($request->departments_id as $department_id) {
                if(!empty($departments_id)) {
                    $departments_id .= ',';
                }

                $departments_id .= $department_id;
            }

            $where .= ' AND calls.department_id IN ('.$departments_id.')';
        }

        if(!empty($request->users_id)) {
            $users_id = '';
            foreach($request->users_id as $user_id) {
                if(!empty($users_id)) {
                    $users_id .= ',';
                }

                $users_id .= $user_id;
            }

            $where .= ' AND call_users.user_id IN ('.$users_id.')';
            $from .= ' INNER JOIN call_users ON call_users.call_id = calls.id';
            $select = "COUNT(*) AS total, SUM(call_users.duration) as total_duration, call_end_id";
        } else {
            $select = "COUNT(*) AS total, SUM(duration) as total_duration, call_end_id";
        }
        
        $sql = "SELECT $select FROM $from WHERE $where GROUP BY call_end_id ORDER BY call_end_id";

        $call_ends = CallEnd::where('company_id', $company_id)
            ->orderBy('name', 'asc')
            ->get();

        $calls[0]['name'] = 'Sin final';
        $calls[0]['total'] = 0;
        $calls[0]['total_duration'] = 0;
        $calls[0]['media_duration'] = 0;
        foreach($call_ends as $call_end) {
            $calls[$call_end->id]['name'] = $call_end->name;
            $calls[$call_end->id]['total'] = 0;
            $calls[$call_end->id]['total_duration'] = 0;
            $calls[$call_end->id]['media_duration'] = 0;
        }

        $registers = DB::select($sql);

        foreach($registers as $register) {
            if(empty($register->call_end_id)) {
                $calls[0]['total'] = $register->total;
                $calls[0]['total_duration'] = $register->total_duration;
                $calls[0]['media_duration'] = $register->total_duration / $register->total;
            } else {
                $calls[$register->call_end_id]['total'] = $register->total;
                $calls[$register->call_end_id]['total_duration'] = $register->total_duration;
                $calls[$register->call_end_id]['media_duration'] = $register->total_duration / $register->total;
            }
        }

        return $calls;
    }

    public function total_in_calls_by_hour(Request $request) {
        
        if(!empty($request->company_id)) {
            $company_id = $request->company_id;
        } else {
            $user = get_loged_user();
            $company_id = $user->company_id;
        }
        
        $from = 'calls';
        $where = 'calls.company_id = '.$company_id.' AND calls.call_type_id = 1'; 

        if(!empty($request->start)) {
            $where .= ' AND calls.start >= '.strtotime($request->start.' 00:00:00');
        } else {
            $where .= ' AND calls.start >= '.strtotime(date('Y-m').'-1 00:00:00');
        }

        if(!empty($request->end)) {
            $where .= ' AND calls.start <= '.strtotime($request->end.' 23:59:59');
        } else {
            $where .= ' AND calls.start <= '.strtotime(date('Y-m-d').' 23:59:59');
        }

        if (!empty($request->campaigns_id)) {
            $campaigns_id = '';
            foreach ($request->campaigns_id as $campaign_id) {
                if (!empty($campaigns_id)) {
                    $campaign_id .= ',';
                }

                $campaigns_id .= $campaign_id;
            }

            $where .= ' AND calls.campaign_id IN (' . $campaigns_id . ')';
        }

        if(!empty($request->departments_id)) {
            $departments_id = '';
            foreach($request->departments_id as $department_id) {
                if(!empty($departments_id)) {
                    $departments_id .= ',';
                }

                $departments_id .= $department_id;
            }

            $where .= ' AND calls.department_id IN ('.$departments_id.')';
        }

        if(!empty($request->users_id)) {
            $users_id = '';
            foreach($request->users_id as $user_id) {
                if(!empty($users_id)) {
                    $users_id .= ',';
                }

                $users_id .= $user_id;
            }

            $where .= ' AND call_users.user_id IN ('.$users_id.')';
            $from .= ' INNER JOIN call_users ON call_users.call_id = calls.id';
        }
        
        $sql = "SELECT  COUNT(*) AS total, HOUR(FROM_UNIXTIME(calls.start)) AS start_hour, call_status_id FROM $from WHERE $where GROUP BY call_status_id, start_hour ORDER BY call_status_id, start_hour";

        $calls = [];
        for($i = 0; $i < 24; $i++) {
            $calls[$i]['success'] = 0;
            $calls[$i]['lost'] = 0;
            $calls[$i]['voicemail'] = 0;
        }

        $registers = DB::select($sql);

        foreach($registers as $register) {
            switch($register->call_status_id) {
                case 3:
                    $calls[$register->start_hour]['success'] = $register->total;
                break;
                case 4:
                    $calls[$register->start_hour]['lost'] = $register->total;
                break;
                case 5:
                    $calls[$register->start_hour]['voicemail'] = $register->total;
                break;
            }
        }

        return $calls;
    }

    public function total_lost_calls_by_hour_and_user(Request $request)
    {
        if(!empty($request->company_id)) {
            $company_id = $request->company_id;
        } else {
            $user = get_loged_user();
            $company_id = $user->company_id;
        }
        $from = 'call_user_calleds INNER JOIN calls ON calls.id = call_user_calleds.call_id';

        $where = 'calls.company_id = ' . $company_id . ' AND calls.call_type_id = 1 AND call_user_calleds.answered = 0 AND call_user_calleds.user_id IS NOT NULL';

        if (!empty($request->start)) {
            $where .= ' AND calls.start >= ' . strtotime($request->start . ' 00:00:00');
        } else {
            $where .= ' AND calls.start >= ' . strtotime(date('Y-m') . '-1 00:00:00');
        }

        if (!empty($request->end)) {
            $where .= ' AND calls.start <= ' . strtotime($request->end . ' 23:59:59');
        } else {
            $where .= ' AND calls.start <= ' . strtotime(date('Y-m-d') . ' 23:59:59');
        }

        if (!empty($request->campaigns_id)) {
            $campaigns_id = '';
            foreach ($request->campaigns_id as $campaign_id) {
                if (!empty($campaigns_id)) {
                    $campaign_id .= ',';
                }

                $campaigns_id .= $campaign_id;
            }

            $where .= ' AND calls.campaign_id IN (' . $campaigns_id . ')';
        }

        if (!empty($request->departments_id)) {
            $departments_id = '';
            foreach ($request->departments_id as $department_id) {
                if (!empty($departments_id)) {
                    $departments_id .= ',';
                }

                $departments_id .= $department_id;
            }

            $where .= ' AND calls.department_id IN (' . $departments_id . ')';
        }

        if (!empty($request->users_id)) {
            $users_id = '';
            foreach ($request->users_id as $user_id) {
                if (!empty($users_id)) {
                    $users_id .= ',';
                }

                $users_id .= $user_id;
            }

            $where .= ' AND call_user_calleds.user_id IN (' . $users_id . ')';
        }

        $sql = "SELECT  COUNT(*) AS total, HOUR(FROM_UNIXTIME(calls.start)) AS start_hour, user_id FROM $from WHERE $where GROUP BY user_id, start_hour ORDER BY user_id, start_hour";

        $calls = [];
        if (!empty($request->users_id)) {
            $users = User::join('user_company', 'user_company.user_id', '=', 'users.id')
                ->where('user_company.company_id', $company_id)
                ->whereIn('id', $request->users_id)
                ->orderBy('name', 'ASC')
                ->select('users.*')
                ->distinct()
                ->get();
        } else if (!empty($request->departments_id)) {
            $users = User::join('user_company', 'user_company.user_id', '=', 'users.id')
                ->where('user_company.company_id', $company_id)
                ->whereIn('department_id', $request->departments_id)
                ->orderBy('name', 'ASC')
                ->select('users.*')
                ->distinct()
                ->get();
        } else {
            $users = User::join('user_company', 'user_company.user_id', '=', 'users.id')
                ->where('user_company.company_id', $company_id)
                ->orderBy('name', 'ASC')
                ->select('users.*')
                ->distinct()
                ->get();
        }

        $userIndex = [];
        $user_i = 0;
        foreach ($users as $user) {
            $calls[$user_i]['name'] = $user->name;
            for ($i = 0; $i < 24; $i++) {
                $calls[$user_i]['lost_calls'][$i] = 0;
            }
            $userIndex[$user->id] = $user_i;
            $user_i++;
        }

        $registers = DB::select($sql);

        foreach ($registers as $register) {
            if (!empty($userIndex[$register->user_id]) || $userIndex[$register->user_id] == 0) {
                $calls[$userIndex[$register->user_id]]['lost_calls'][$register->start_hour] = $register->total;
            }
        }

        return $calls;
    }

    public function total_calls_by_user(Request $request) {
        if(!empty($request->company_id)) {
            $company_id = $request->company_id;
        } else {
            $user = get_loged_user();
            $company_id = $user->company_id;
        }
        $from = 'calls';
        $from .= ' INNER JOIN call_users ON calls.id = call_users.call_id';
        $from .= ' INNER JOIN users ON users.id = call_users.user_id';

        $where = 'calls.company_id = '.$company_id; 

        if(!empty($request->start)) {
            $where .= ' AND call_users.start >= '.strtotime($request->start.' 00:00:00');
        } else {
            $where .= ' AND call_users.start >= '.strtotime(date('Y-m').'-1 00:00:00');
        }

        if(!empty($request->end)) {
            $where .= ' AND call_users.start <= '.strtotime($request->end.' 23:59:59');
        } else {
            $where .= ' AND call_users.start <= '.strtotime(date('Y-m-d').' 23:59:59');
        }

        if (!empty($request->campaigns_id)) {
            $campaigns_id = '';
            foreach ($request->campaigns_id as $campaign_id) {
                if (!empty($campaigns_id)) {
                    $campaign_id .= ',';
                }

                $campaigns_id .= $campaign_id;
            }

            $where .= ' AND calls.campaign_id IN (' . $campaigns_id . ')';
        }

        if(!empty($request->departments_id)) {
            $departments_id = '';
            foreach($request->departments_id as $department_id) {
                if(!empty($departments_id)) {
                    $departments_id .= ',';
                }

                $departments_id .= $department_id;
            }

            $where .= ' AND users.department_id IN ('.$departments_id.')';
        }

        if(!empty($request->users_id)) {
            $users_id = '';
            foreach($request->users_id as $user_id) {
                if(!empty($users_id)) {
                    $users_id .= ',';
                }

                $users_id .= $user_id;
            }

            $where .= ' AND call_users.user_id IN ('.$users_id.')';
        }
        
        $sql = "SELECT COUNT(*) AS total, SUM(call_users.duration) AS total_duration, users.id AS user_id, calls.call_type_id FROM $from WHERE $where GROUP BY users.id, calls.call_type_id";

        if (!empty($request->users_id)) {
            $users = User::whereIn('id', $request->users_id)
                ->orderBy('name', 'ASC')
                ->get();
        } elseif (!empty($request->departments_id)) {
            $users = User::whereIn('department_id', $request->departments_id)
                ->orderBy('name', 'ASC')
                ->get();
        } else {
            $users = User::where('company_id', $company_id)
                ->orderBy('name', 'ASC')
                ->get();
        }
        
        $i = 0;
        foreach($users as $user) {
            $calls[$i]['name'] = $user->name;
            $calls[$i]['in']['total'] = 0;
            $calls[$i]['in']['total_duration'] = 0;
            $calls[$i]['out']['total'] = 0;
            $calls[$i]['out']['total_duration'] = 0;
            $calls[$i]['internal']['total'] = 0;
            $calls[$i]['internal']['total_duration'] = 0;

            $user_index[$user->id] = $i;
            $i++;
        }

        $registers = DB::select($sql);

        foreach($registers as $register) {
            if (!empty($user_index[$register->user_id]) || $user_index[$register->user_id] == 0) {
                switch($register->call_type_id) {
                    case 1:
                        $calls[$user_index[$register->user_id]]['in']['total'] = $register->total;
                        $calls[$user_index[$register->user_id]]['in']['total_duration'] = $register->total_duration;
                    break;
                    case 2:
                        $calls[$user_index[$register->user_id]]['out']['total'] = $register->total;
                        $calls[$user_index[$register->user_id]]['out']['total_duration'] = $register->total_duration;
                    break;
                    case 3:
                        $calls[$user_index[$register->user_id]]['internal']['total'] = $register->total;
                        $calls[$user_index[$register->user_id]]['internal']['total_duration'] = $register->total_duration;
                    break;
                }
            }
        }

        return $calls;
    }

    public function total_out_calls_by_hour(Request $request) {
        if(!empty($request->company_id)) {
            $company_id = $request->company_id;
        } else {
            $user = get_loged_user();
            $company_id = $user->company_id;
        }

        $from = 'calls';
        $where = 'calls.company_id = '.$company_id.' AND calls.call_type_id = 2'; 

        if(!empty($request->start)) {
            $where .= ' AND calls.start >= '.strtotime($request->start.' 00:00:00');
        } else {
            $where .= ' AND calls.start >= '.strtotime(date('Y-m').'-1 00:00:00');
        }

        if(!empty($request->end)) {
            $where .= ' AND calls.start <= '.strtotime($request->end.' 23:59:59');
        } else {
            $where .= ' AND calls.start <= '.strtotime(date('Y-m-d').' 23:59:59');
        }

        if (!empty($request->campaigns_id)) {
            $campaigns_id = '';
            foreach ($request->campaigns_id as $campaign_id) {
                if (!empty($campaigns_id)) {
                    $campaign_id .= ',';
                }

                $campaigns_id .= $campaign_id;
            }

            $where .= ' AND calls.campaign_id IN (' . $campaigns_id . ')';
        }

        if(!empty($request->departments_id)) {
            $departments_id = '';
            foreach($request->departments_id as $department_id) {
                if(!empty($departments_id)) {
                    $departments_id .= ',';
                }

                $departments_id .= $department_id;
            }

            $where .= ' AND calls.department_id IN ('.$departments_id.')';
        }

        if(!empty($request->users_id)) {
            $users_id = '';
            foreach($request->users_id as $user_id) {
                if(!empty($users_id)) {
                    $users_id .= ',';
                }

                $users_id .= $user_id;
            }

            $where .= ' AND call_users.user_id IN ('.$users_id.')';
            $from .= ' INNER JOIN call_users ON call_users.call_id = calls.id';
        }
        
        $sql = "SELECT  COUNT(*) AS total, HOUR(FROM_UNIXTIME(calls.start)) AS start_hour FROM $from WHERE $where GROUP BY start_hour ORDER BY start_hour";

        $calls = [];
        for($i = 0; $i < 24; $i++) {
            $calls[$i]['success'] = 0;
        }

        $registers = DB::select($sql);

        foreach($registers as $register) {
            $calls[$register->start_hour]['success'] = $register->total;
        }

        return $calls;
    }

    public function total_user_sessions(Request $request) {
        if(!empty($request->company_id)) {
            $company_id = $request->company_id;
        } else {
            $user = get_loged_user();
            $company_id = $user->company_id;
        }
        $from = 'user_sessions';
        $from .= ' INNER JOIN users ON users.id = user_sessions.user_id';

        $where = 'users.company_id = '.$company_id; 

        if(!empty($request->start)) {
            $where .= ' AND user_sessions.start >= "'.date('Y-m-d H:i:s', strtotime($request->start.' 00:00:00')).'"';
        } else {
            $where .= ' AND user_sessions.start >= "'.date('Y-m-d H:i:s', strtotime(date('Y-m').'-1 00:00:00')).'"';
        }

        if(!empty($request->end)) {
            $where .= ' AND user_sessions.start <= "'.date('Y-m-d H:i:s', strtotime($request->end.' 23:59:59')).'"';
        } else {
            $where .= ' AND user_sessions.start <= "'.date('Y-m-d H:i:s', strtotime(date('Y-m-d').' 23:59:59')).'"';
        }

        if(!empty($request->departments_id)) {
            $departments_id = '';
            foreach($request->departments_id as $department_id) {
                if(!empty($departments_id)) {
                    $departments_id .= ',';
                }

                $departments_id .= $department_id;
            }

            $where .= ' AND users.department_id IN ('.$departments_id.')';
        }

        if(!empty($request->users_id)) {
            $users_id = '';
            foreach($request->users_id as $user_id) {
                if(!empty($users_id)) {
                    $users_id .= ',';
                }

                $users_id .= $user_id;
            }

            $where .= ' AND users.id IN ('.$users_id.')';
        }
        
        $sql = "SELECT  SUM(duration) AS total_duration, users.id FROM $from WHERE $where GROUP BY users.id";
        
        if (!empty($request->users_id)) {
            $users = User::whereIn('id', $request->users_id)
                ->orderBy('name', 'ASC')
                ->get();
        } elseif (!empty($request->departments_id)) {
            $users = User::whereIn('department_id', $request->departments_id)
                ->orderBy('name', 'ASC')
                ->get();
        } else {
            $users = User::where('company_id', $company_id)
                ->orderBy('name', 'ASC')
                ->get();
        }

        $i = 0;
        $user_index = [];
        $sessions = [];

        foreach($users as $user) {
            $sessions[$i]['name'] = $user->name;
            $sessions[$i]['duration'] = 0;

            $user_index[$user->id] = $i;
            $i++;
        }

        $registers = DB::select($sql);

        foreach($registers as $register) {
            $sessions[$user_index[$register->id]]['duration'] = $register->total_duration;
        }

        return $sessions;
    }

    public function total_user_break_times(Request $request) {
        if(!empty($request->company_id)) {
            $company_id = $request->company_id;
        } else {
            $user = get_loged_user();
            $company_id = $user->company_id;
        }
        $from = 'break_time_users';
        $from .= ' INNER JOIN users ON users.id = break_time_users.user_id';
        $from .= ' INNER JOIN break_times ON break_times.id = break_time_users.break_time_id';

        $where = 'users.company_id = '.$company_id; 

        if(!empty($request->start)) {
            $where .= ' AND break_time_users.start >= '.date('YmdHis', strtotime($request->start.' 00:00:00'));
        } else {
            $where .= ' AND break_time_users.start >= '.date('YmdHis', strtotime(date('Y-m').'-1 00:00:00'));
        }

        if(!empty($request->end)) {
            $where .= ' AND break_time_users.start <= '.date('YmdHis', strtotime($request->end.' 23:59:59'));
        } else {
            $where .= ' AND break_time_users.start <= '.date('YmdHis', strtotime(date('Y-m-d').' 23:59:59'));
        }

        if(!empty($request->departments_id)) {
            $departments_id = '';
            foreach($request->departments_id as $department_id) {
                if(!empty($departments_id)) {
                    $departments_id .= ',';
                }

                $departments_id .= $department_id;
            }

            $where .= ' AND users.department_id IN ('.$departments_id.')';
        }

        if(!empty($request->users_id)) {
            $users_id = '';
            foreach($request->users_id as $user_id) {
                if(!empty($users_id)) {
                    $users_id .= ',';
                }

                $users_id .= $user_id;
            }

            $where .= ' AND users.id IN ('.$users_id.')';
        }
        
        $sql = "SELECT  SUM(duration) AS total_duration, break_time_id, user_id FROM $from WHERE $where GROUP BY break_time_id, user_id";
        
        if (!empty($request->users_id)) {
            $users = User::whereIn('id', $request->users_id)
                ->orderBy('name', 'ASC')
                ->get();
        } elseif (!empty($request->departments_id)) {
            $users = User::whereIn('department_id', $request->departments_id)
                ->orderBy('name', 'ASC')
                ->get();
        } else {
            $users = User::where('company_id', $company_id)
                ->orderBy('name', 'ASC')
                ->get();
        }

        $bks = BreakTime::orderBy('name', 'asc')
            ->get();

        $i = 0;
        $user_index = [];
        $break_time_index = [];
        $break_times = [];
        foreach($users as $user) {
            $break_times[$i]['name'] = $user->name;
            $j = 0;
            foreach($bks as $break_time) {
                $break_times[$i]['break_times'][$j]['name'] = $break_time->name;
                $break_times[$i]['break_times'][$j]['duration'] = 0;

                $break_time_index[$break_time->id] = $j;
                $j++;
            }

            $user_index[$user->id] = $i;
            $i++;
        }

        $registers = DB::select($sql);

        foreach($registers as $register) {
            $break_times[$user_index[$register->user_id]]['break_times'][$break_time_index[$register->break_time_id]]['duration'] = $register->total_duration;
        }

        return $break_times;
    }
}
