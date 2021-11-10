<?php

namespace App\Broadcasting;

use App\Company;

class CompanyChannel
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
     * Authenticate the company's access to the channel.
     *
     * @param  \App\Company  $company
     * @return array|bool
     */
    public function join(Company $company)
    {
        return true;
    }
}
