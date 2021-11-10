<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['key', 'name', 'url', 'help'];

    /**
     * @author Roger Corominas
     * Devuelve un listado con todas las pestañas del módulo
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tabs() {
        return $this->hasMany('App\Tab')->orderBy('position', 'ASC');
    }

    public function module_table_rows()
    {
        return $this->hasMany('App\ModuleTableRow')->orderBy('position', 'ASC');
    }
}
