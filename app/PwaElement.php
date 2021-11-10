<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PwaElement extends Model
{
    protected $fillable = ['pwa_page_id', 'pwa_element_type_id', 'pwa_language_id', 'title', 'content', 'position', 'is_private', 'visibility'];

    public function pwa_element_type() {
        return $this->belongsTo('App\PwaElementType');
    }

    public function pwa_language() {
        return $this->belongsTo('App\PwaLanguage');
    }
}
