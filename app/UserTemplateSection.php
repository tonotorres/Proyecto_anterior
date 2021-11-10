<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTemplateSection extends Model
{
    protected $fillable = ['user_template_tab_id', 'section_id', 'name', 'position'];

    /**
     * @author Roger Corominas
     * Devuelve un listado con todos los campos que forman parte de la sección actual
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function user_template_fields() {
        return $this->hasMany('App\UserTemplateField')->orderBy('position', 'ASC');
    }

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la sección des de donde fue generada
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function section() {
        return $this->belongsTo('App\Section');
    }
}
