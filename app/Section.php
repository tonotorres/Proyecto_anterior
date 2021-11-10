<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['tab_id', 'name', 'position'];

    /**
     * @author Roger Corominas
     * Devuelve un listado con todos los campos de la sección
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fields() {
        return $this->hasMany('App\Field')->orderBy('position', 'ASC');
    }
}
