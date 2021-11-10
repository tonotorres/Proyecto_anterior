<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Text extends Model
{
    protected $fillable = ['company_id', 'language_id', 'key', 'text', 'is_html'];

    /**
     * @author Roger Corominas
     * Devuelve la relación con el idioma
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function language() {
        return $this->belongsTo('App\Language');
    }
}
