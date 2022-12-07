<?php

namespace Nachopitt\Migrations;

use Nachopitt\Migrations\MigrationCreator;
use Nachopitt\Migrations\Console\Commands\MigrateImportCommand;

class MigrationServiceProvider extends \Illuminate\Database\MigrationServiceProvider
{
    public function __construct($app)
    {
        $this->commands = [
            'MigrateImport' => MigrateImportCommand::class
        ];
        parent::__construct($app);
    }

    /**
     * Register the migration creator.
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->app->singleton('migration.creator', function ($app) {
            return new MigrationCreator($app['files'], $app->basePath('stubs'));
        });
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
