<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactType extends Model
{
    protected $fillable = ['name'];

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los servicios de esa forma de contacto
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contact_type_services() {
        return $this->hasMany('App\ContactTypeService');
    }
}
