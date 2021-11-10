<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'project_priority_id', 'project_status_id', 'account_id', 'name', 'code', 'color', 'start', 'end', 'finish'];

    public function project_priority() {
        return $this->belongsTo('App\ProjectPriority');
    }

    public function project_status() {
        return $this->belongsTo('App\ProjectStatus');
    }

    public function account() {
        return $this->belongsTo('App\Account');
    }

    public function project_description() {
        return $this->hasOne('App\ProjectDescription');
    }

    public function project_comments() {
        return $this->hasMany('App\ProjectComment');
    }

    public function project_stages() {
        return $this->hasMany('App\ProjectStage');
    }

    public function users() {
        return $this->belongsToMany('App\User', 'project_user')->withTrashed();
    }
}
