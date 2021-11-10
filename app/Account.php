<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;
    protected $fillable = ['account_type_id', 'account_id', 'company_id', 'code', 'name', 'corporate_name', 'vat_number', 'url', 'contact'];

    /**
     * @author Roger Corominas
     * Devuelve un objeto con el tipo de cuenta
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account_type()
    {
        return $this->belongsTo('App\AccountType');
    }

    public function account_description()
    {
        return $this->hasOne(AccountDescription::class);
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los contactos de la cuenta
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contacts()
    {
        return $this->hasMany('App\Contact');
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los emails activos de la cuenta
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function emails()
    {
        return $this->hasMany('App\AccountContactType')->where('contact_type_id', 2);
    }

    /**
     * @author Roger Corominas
     * Devuelve un array con todos los teléfonos activos de la cuenta
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function phones()
    {
        return $this->hasMany('App\AccountContactType')->where('contact_type_id', 1);
    }

    public function addresses()
    {
        return $this->hasMany('App\AccountAddress');
    }

    public function address_book_destinations()
    {
        return $this->hasMany('App\AddressBookDestination', 'reference_id')->where('module_id', 8);
    }

    public function tags()
    {
        return $this->belongsToMany('App\Tag', 'tag_module', 'reference_id', 'tag_id')->where('module_key', 8);
    }

    public function parent_account()
    {
        return $this->belongsTo('App\Account', 'account_id');
    }

    public function subaccounts()
    {
        return $this->hasMany('App\Account');
    }

    public function last_calls()
    {
        return $this->hasMany('App\Call')
        ->orderBy('id', 'desc')
        ->limit(10);
    }

    public function open_projects()
    {
        return $this->hasMany('App\Project')
        ->where('finish', '0')
            ->orderBy('end', 'asc');
    }

    public function projects()
    {
        return $this->hasMany('App\Project')
        ->orderBy('end', 'asc');
    }

    public function comments()
    {
        return $this->hasMany('App\AccountComment')
        ->orderBy('created_at', 'desc');
    }
}
