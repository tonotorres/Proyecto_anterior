<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskList extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'account_id', 'project_id', 'name', 'finish'];

    public function account() {
        return $this->belongsTo('App\Account')->withTrashed();
    }

    public function project() {
        return $this->belongsTo('App\Project')->withTrashed();
    }

    public function users() {
        return $this->belongsToMany('App\User', 'task_list_user')->withTrashed();
    }
}
