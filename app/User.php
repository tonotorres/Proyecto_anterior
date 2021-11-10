<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id', 'user_template_id', 'user_type_id', 'department_id', 'account_id', 'signin_break_time_id', 'name', 'extension', 'email', 'username', 'password', 'api_token', 'is_active', 'always_online', 'image', 'keep_alive_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * @author Roger Corominas
     * Relación con todas las empresas con las que puede trabajar un usuario
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function companies() {
        return $this->belongsToMany('App\Company', 'user_company')->orderBy('name', 'asc');
    }

    /**
     * @author Roger Corominas
     * Relación con el tipo de usuario
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user_type() {
        return $this->belongsTo('App\UserType');
    }

    /**
     * @author Roger Corominas
     * Relación con la plantilla de usuario
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user_template() {
        return $this->belongsTo('App\UserTemplate');
    }

    /**
     * @author Roger Corominas
     * Relación con el department
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function department() {
        return $this->belongsTo('App\Department');
    }

    /**
     * @author Roger Corominas
     * Relación con todos los chat_rooms
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function chat_rooms() {
        return $this->belongsToMany('App\ChatRoom', 'user_chat_room')->withPivot('name', 'unread', 'last_connection_at', 'last_read_message_id', 'is_online');
    }

    public function paused_queues() {
        return $this->belongsToMany('App\Queue', 'queue_user_paused');
    }

    public function active_session() {
        return $this->hasOne('App\UserSession')
            ->whereNull('end')
            ->orderBy('id', 'desc');
    }

    public function user_extensions() {
        return $this->hasMany('App\UserExtension');
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_user');
    }

    public function scopeGetCompanyUsers($query, $company_id) {
        return $query->join('user_company', 'user_company.user_id', '=', 'users.id')
            ->where('user_company.company_id', $company_id)
            ->select('users.*')
            ->distinct();
    }

    public function scopeGetUserCustoms($query)
    {
        return $query->leftJoin('user_customs', 'user_customs.user_id', '=', 'users.id')
        ->select('user_customs.*', 'users.*');
    }
}
