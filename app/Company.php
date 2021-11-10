<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'logo'];

    /**
     * @author Roger Corominas
     * Devuelve un listado con todas las configuraciones de la emprsa seleccionada.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function company_configs () {
        return $this->hasMany('App\CompanyConfig')->orderBy('position', 'asc');
    }
}
