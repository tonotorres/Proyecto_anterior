<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tab extends Model
{
    protected $fillable = ['module_id', 'name', 'position'];

    /**
     * @author Roger Corominas
     * Devuelve un listado con las secciones de la pestaña
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sections() {
        return $this->hasMany('App\Section')->orderBy('position', 'ASC');
    }
}
