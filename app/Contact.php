<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'account_id', 'name', 'birthday'];

    /**
     * @author Roger Corominas
     * Devuelve un objeto con la cuenta asociada al contacto
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account() {
        return $this->belongsTo('App\Account');
    }
    /**
     * @author Roger Corominas
     * Devuelve un array con todos los emails activos del contacto
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function emails() {
        return $this->hasMany('App\ContactContactType')->where('contact_type_id', 2);
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los teléfonos activos del contacto
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function phones() {
        return $this->hasMany('App\ContactContactType')->where('contact_type_id', 1);
    }

    public function contact_contact_types() {
        return $this->hasMany('App\ContactContactType');
    }

    public function tags() {
        return $this->belongsToMany('App\Tag', 'tag_module', 'reference_id', 'tag_id')->where('module_key', 6);
    }
}
