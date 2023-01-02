<?php

namespace Nachopitt\Migrations\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Support\Facades\File;
use Nachopitt\Migrations\Writers\MigrationDefinitionWriter;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;

class MigrateImportCommand extends MigrateMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:import {file? : The SQL file to be imported}
        {--schema= : The name of the schema}
        {--path= : The location where the migration file should be created}
        {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
        {--fullpath : Output the full path of the migration}
        {--squash : Generate one migration file instead of multiple files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration file by importing a SQL file';

    /**
     * The migration creator instance.
     *
     * @var \Nachopitt\Migrations\MigrationCreator
     */
    protected $creator;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $defaultDatabase = config("database.connections.mysql.database");
        $schemaName = $this->option('schema') ?: $defaultDatabase;
        $sqlImportFile = $this->argument('file') ?: "database_model/${defaultDatabase}.sql";
        $squash = $this->option('squash');

        $sqlImportFileContents = File::get($sqlImportFile);

        $parser = new Parser($sqlImportFileContents);
        $migrationWriter = new MigrationDefinitionWriter;

        foreach($parser->statements as $statement) {
            if ($statement instanceof CreateStatement && in_array('TABLE', $statement->options->options)) {
                $migrationWriter->handleCreateTableStatement($statement);

                if (!$squash) {
                    $upDefinition = $migrationWriter->getUpDefinition();
                    $this->creator->setUpDefinition($upDefinition->get());

                    $downDefinition = $migrationWriter->getDownDefinition();
                    $this->creator->setDownDefinition($downDefinition->get());

                    $this->writeMigration(sprintf('create_%s_table', $statement->name->table), $statement->name->table, true);

                    $migrationWriter->reset();
                }
            }
            else if ($statement instanceof AlterStatement) {
                $migrationWriter->handleAlterTableStatement($statement);

                $upDefinition = $migrationWriter->getUpDefinition();
                $this->creator->setUpDefinition($upDefinition->get());

                $downDefinition = $migrationWriter->getDownDefinition();
                $this->creator->setDownDefinition($downDefinition->get());

                $this->writeMigration(sprintf('update_%s_table', $statement->table->table), $statement->table->table, true);

                $migrationWriter->reset();
            }
            else if ($statement instanceof DropStatement) {
                
            }
        }

        if ($squash) {
            $upDefinition = $migrationWriter->getUpDefinition();
            $this->creator->setUpDefinition($upDefinition->get());

            $downDefinition = $migrationWriter->getDownDefinition();
            $this->creator->setDownDefinition($downDefinition->get());

            $this->writeMigration(sprintf('create_%s_database', $schemaName), $schemaName, true);

            $migrationWriter->reset();
        }

        $this->composer->dumpAutoloads();

        config(["database.connections.mysql.database" => $schemaName]);

        $this->info("Import SQL file $sqlImportFile into a new $schemaName migration finished successfully!");

        return Command::SUCCESS;
    }

}
