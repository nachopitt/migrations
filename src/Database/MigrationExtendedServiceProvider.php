<?php

namespace Nachopitt\Database;

use App\Console\Commands\MigrateImportCommand;
use Illuminate\Database\MigrationServiceProvider;

class MigrationExtendedServiceProvider extends MigrationServiceProvider
{
    public function __construct($app)
    {
        $this->commands['MigrateImport'] = MigrateImportCommand::class;
        parent::__construct($app);
    }
}