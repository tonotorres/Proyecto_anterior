<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModuleTableRow extends Model
{
    protected $fillable = ['module_id', 'name', 'label', 'form', 'background', 'position'];

    public function module_table_row_actions()
    {
        return $this->hasMany('App\ModuleTableRowAction')
        ->orderBy('position', 'asc');
    }
}
