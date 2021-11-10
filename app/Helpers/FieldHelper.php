<?php

use App\UserTemplateField;

if(!function_exists('get_user_template_fields_validations')) {
    /**
     * @author Roger Corominas
     * Devuelve un array con todas las validaciones del módulo identificado por $module_id para la plantilla de usuario identificada $user_template_id
     * @param int $user_template_id
     * @param int $module_id
     * @param int $id
     * @return array Listado de validaciones con pareja name => validation
     */
    function get_user_template_fields_validations(int $user_template_id, int $module_id, int $id = null) {
        //RC: Obtenemos las validaciones para todos los campos
        $object_validations = UserTemplateField::generateQueryValidations($user_template_id, $module_id)
            ->get();

        //RC: Creamos la estroctura nombre => validaciones
        $validations = array();
        foreach($object_validations as $validation) {
            if (is_null($id)) {
                $validations[$validation->name] = $validation->validations_create;
            } else {
                $validations[$validation->name] = str_replace('##ID##', $id, $validation->validations_update);
            }
        }

        return $validations;
    }
}

if(!function_exists('get_user_template_fields_partial_validations')) {
    /**
     * @author Roger Corominas
     * Devuelve un array con todas las validaciones del módulo identificado por $module_id para la plantilla de usuario identificada $user_template_id
     * @param int $user_template_id
     * @param int $module_id
     * @param int $id
     * @return array Listado de validaciones con pareja name => validation
     */
    function get_user_template_fields_partial_validations(int $user_template_id, int $module_id, $data,int $id = null) {
        //RC: Obtenemos todas las claves
        $keys = [];
        foreach($data as $key => $value) {
            $keys[] = $key;
        }

        //RC: Obtenemos las validaciones para todos los campos
        $object_validations = UserTemplateField::generateQueryValidations($user_template_id, $module_id)
            ->whereIn('user_template_fields.name', $keys)
            ->get();

        //RC: Creamos la estroctura nombre => validaciones
        $validations = array();
        foreach($object_validations as $validation) {
            if (is_null($id)) {
                $validations[$validation->name] = $validation->validations_create;
            } else {
                $validations[$validation->name] = str_replace('##ID##', $id, $validation->validations_update);
            }
        }

        return $validations;
    }
}
