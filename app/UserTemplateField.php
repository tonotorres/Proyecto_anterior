<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTemplateField extends Model
{
    protected $fillable = ['user_template_section_id', 'field_id', 'field_type_id', 'key', 'width', 'label', 'name', 'default', 'validations_create', 'validations_update', 'options', 'position'];

    /**
     * @author Roger Corominas
     * devuelve el objeto campo des del que fue generado
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function field() {
        return $this->belongsTo('App\Field');
    }

    /**
     * @author Roger Corominas
     * Dvuelve el objeto del tipo de campo
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function field_type() {
        return $this->belongsTo('App\FieldType');
    }

    /**
     * @author Roger Corominas
     * Prepara el objeto para devolver un listado de todas las validaciones para la plantilla identificada por
     * el $user_template_id y el módulo identificado por $module_id. El listado que podremos obtener estará indexado por el campo name.
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $user_template_id identificador de la plantilla de usuario
     * @param int $module_id identificador dle módulo deseado
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGenerateQueryValidations($query, $user_template_id, $module_id) {
        return $query->join('user_template_sections', 'user_template_fields.user_template_section_id', '=', 'user_template_sections.id')
            ->join('user_template_tabs', 'user_template_sections.user_template_tab_id', '=', 'user_template_tabs.id')
            ->join('user_template_modules', 'user_template_tabs.user_template_module_id', '=', 'user_template_modules.id')
            ->where('user_template_id', $user_template_id)
            ->where('module_id', $module_id)
            ->select('user_template_fields.name', 'user_template_fields.validations_create', 'user_template_fields.validations_update');
    }
}
