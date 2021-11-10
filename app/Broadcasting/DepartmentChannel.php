<?php

namespace App\Broadcasting;

use App\Department;


class DepartmentChannel
{
    /**
     * Create a new channel instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     *
     * @param  \App\Department  $department
     * @return array|bool
     */
    public function join(Department $department)
    {
        return true;
    }
}
