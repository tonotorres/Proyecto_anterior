<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $fillable = [
        'section_id', 'field_type_id', 'key', 'width', 'label', 'name', 'default', 'validations_create', 'validations_update', 'options', 'position',
    ];

    /**
     * RC: Obtener el tipo de campo
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function field_type() {
        return $this->belongsTo('App\FieldType');
    }

    /**
     * RC: Obtener los campos de las plantillas
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function user_template_fields() {
        return $this->hasMany('App\UserTemplateField');
    }
}
