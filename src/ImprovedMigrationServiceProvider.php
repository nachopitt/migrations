<?php

namespace Nachopitt\ImprovedMigrationCommands;

use Nachopitt\ImprovedMigrationCommands\Console\Commands\MigrateImportCommand;
use Illuminate\Database\MigrationServiceProvider;

class ImprovedMigrationServiceProvider extends MigrationServiceProvider
{
    public function __construct($app)
    {
        $this->commands['MigrateImport'] = MigrateImportCommand::class;
        parent::__construct($app);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateImportCommand()
    {
        $this->app->singleton(MigrateImportCommand::class, function ($app) {
            // Once we have the migration creator registered, we will create the command
            // and inject the creator. The creator is responsible for the actual file
            // creation of the migrations, and may be extended by these developers.
            $creator = $app['migration.creator'];

            $composer = $app['composer'];

            return new MigrateImportCommand($creator, $composer);
        });
    }
}
