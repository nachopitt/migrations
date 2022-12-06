<?php

namespace Nachopitt\Migration;

class ConsoleSupportServiceProvider extends \Illuminate\Foundation\Providers\ConsoleSupportServiceProvider
{
    public function __construct($app)
    {
        $this->providers[] = MigrationServiceProvider::class;
        parent::__construct($app);
    }
}
