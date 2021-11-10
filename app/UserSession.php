<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'extension', 'start', 'end', 'ip_start', 'ip_end', 'latitude_start', 'longitude_start', 'latitude_end', 'longitude_end', 'ip_error', 'coord_error'
    ];
    
    /**
     * @author: Roger Corominas
     * Devuelve la relación con el usuario.
     * @return App\User
     */
    public function user() {
        return $this->belongsTo('App\User');
    }
}
