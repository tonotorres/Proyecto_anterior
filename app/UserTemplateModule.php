<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTemplateModule extends Model
{
    protected $fillable = ['user_template_id', 'module_id', 'name', 'create', 'read', 'list', 'own', 'update', 'delete'];

    /**
     * @author Roger Corominas
     * Devuelve un listado con todos las pestañas del módulo y la plantilla indicados
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function user_template_tabs () {
        return $this->hasMany('App\UserTemplateTab')->orderBy('position', 'asc');
    }

    public function user_template()
    {
        return $this->belongsTo('App\UserTemplate');
    }

    /**
     * @author Roger Corominas
     * Devuelve el objeto del módulo des del que fue generado el objeto.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function module() {
        return $this->belongsTo('App\Module');
    }

    /**
     * @author Roger Corominas
     * Genera la consulta necesaria para obtener el módulo identificado por la clave $module_key para la plantilla de usuario identificada
     * por $user_template_id
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $user_template_id Identificador de la plantilla
     * @param int $module_key clave del módulo deseado
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGenerateQueryModuleByUserTempalateModuleKey($query, $user_template_id, $module_key) {
        return $query->join('modules', 'user_template_modules.module_id', '=', 'modules.id')
            ->where('user_template_id', $user_template_id)
            ->where('modules.key', $module_key)
            ->select('user_template_modules.*');
    }
}
