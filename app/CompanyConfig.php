<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyConfig extends Model
{
    protected $fillable = ['company_id', 'company_config_group_id', 'key', 'label', 'value', 'position'];

    /**
     * @author Roger Corominas
     * Devuelve el objeto de la agrupación de la configuración
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company_config_group () {
        return $this->belongsTo('App\CompanyConfigGroup');
    }
}
