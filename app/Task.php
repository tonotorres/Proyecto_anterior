<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'task_list_id', 'task_id', 'task_type_id', 'task_priority_id', 'task_status_id', 'name', 'start', 'end', 'duration', 'finish', 'billable'];

    public function task_list() {
        return $this->belongsTo('App\TaskList')->withTrashed();
    }

    public function task_type() {
        return $this->belongsTo('App\TaskType')->withTrashed();
    }

    public function parent_task() {
        return $this->belongsTo('App\Task', 'task_id');
    }

    public function children_tasks() {
        return $this->hasMany('App\Task');
    }

    public function task_priority() {
        return $this->belongsTo('App\TaskPriority')->withTrashed();
    }

    public function task_status() {
        return $this->belongsTo('App\TaskStatus')->withTrashed();
    }

    public function task_description() {
        return $this->hasOne('App\TaskDescription');
    }

    public function task_comments() {
        return $this->hasMany('App\TaskComment');
    }

    public function task_times() {
        return $this->hasMany('App\TaskTime');
    }

    public function users() {
        return $this->belongsToMany('App\User', 'task_user')->withTrashed();
    }

    public function scopeGetUserTasks($query, $company_id, $user_id) {
        return $query->join('task_user', 'task_user.task_id', '=', 'tasks.id')
            ->where('tasks.company_id', $company_id)
            ->where('task_user.user_id', $user_id)
            ->select('tasks.*');
    }
}
