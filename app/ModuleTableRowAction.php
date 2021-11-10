<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModuleTableRowAction extends Model
{
    protected $fillable = ['module_table_row_id', 'name', 'action', 'position'];
}
