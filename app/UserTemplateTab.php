<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTemplateTab extends Model
{
    protected $fillable = ['user_template_module_id', 'tab_id', 'name', 'position'];

    /**
     * @author Roger Corominas
     * Devuelve un listado con todas las secciones de dentro de la pestaña
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function user_template_sections () {
        return $this->hasMany('App\UserTemplateSection')->orderBy('position', 'asc');
    }

    /**
     * @author Roger Corominas
     * Devuelve el objeto pestaña des del que fue generado
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tab() {
        return $this->belongsTo('App\Tab');
    }
}
