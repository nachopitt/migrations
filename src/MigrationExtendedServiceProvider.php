<?php

namespace App\Providers;

use App\Console\Migration\MigrateCommand;
use Illuminate\Database\MigrationServiceProvider;

class MigrationExtendedServiceProvider extends MigrationServiceProvider
{
    public function __construct($app)
    {
        $this->commands['MigrateImport'] = MigrateCommand::class;
        parent::__construct($app);
    }
}