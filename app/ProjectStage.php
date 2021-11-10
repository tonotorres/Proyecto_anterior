<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectStage extends Model
{
    use SoftDeletes;

    protected $fillable = ['project_id', 'project_stage_status_id', 'name', 'start', 'end', 'position', 'finish'];

    public function project_stage_status() {
        return $this->belongsTo('App\ProjectStageStatus');
    }

    public function project() {
        return $this->belongsTo('App\Project');
    }
}
