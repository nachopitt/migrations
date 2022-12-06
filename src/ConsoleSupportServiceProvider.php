<?php

namespace Nachopitt\ImprovedMigrationCommands;

class ConsoleSupportServiceProvider extends \Illuminate\Foundation\Providers\ConsoleSupportServiceProvider
{
    public function __construct($app)
    {
        $this->providers[] = ImprovedMigrationServiceProvider::class;
        parent::__construct($app);
    }
}
