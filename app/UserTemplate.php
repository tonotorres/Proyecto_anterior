<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTemplate extends Model
{
    protected $fillable = ['company_id', 'name', 'weight'];

    /**
     * @author Roger Corominas
     * devuelve un listado con todos los permisos de los distintos modulos para la plantilla seleccionada
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function user_template_modules () {
        return $this->hasMany('App\UserTemplateModule');
    }
}
