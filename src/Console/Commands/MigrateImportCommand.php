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

        $createDefinitions = [];
        $alterDefinitions = [];
        $dropDefinitions = [];

        foreach($parser->statements as $statement) {
            if ($statement instanceof CreateStatement && in_array('TABLE', $statement->options->options)) {
                $migrationWriter->handleCreateTableStatement($statement);

                if (!$squash) {
                    $this->creator->setUpDefinition($migrationWriter->getUpDefinition());
                    $this->creator->setDownDefinition($migrationWriter->getDownDefinition());

                    $this->writeMigration(sprintf('create_%s_table', $statement->name->table), $statement->name->table, true);
                }
                else {
                    $createDefinitions[] = [
                        'up' => $migrationWriter->getUpDefinition(),
                        'down' => $migrationWriter->getDownDefinition()
                    ];
                }

                $migrationWriter->reset();
            }
            else if ($statement instanceof AlterStatement && in_array('TABLE', $statement->options->options)) {
                $migrationWriter->handleAlterTableStatement($statement);

                if (!$squash) {
                    $this->creator->setUpDefinition($migrationWriter->getUpDefinition());
                    $this->creator->setDownDefinition($migrationWriter->getDownDefinition());

                    $this->writeMigration(sprintf('update_%s_table', $statement->table->table), $statement->table->table, true);
                }
                else {
                    $alterDefinitions[] = [
                        'up' => $migrationWriter->getUpDefinition(),
                        'down' => $migrationWriter->getDownDefinition()
                    ];
                }

                $migrationWriter->reset();
            }
            else if ($statement instanceof DropStatement && in_array('TABLE', $statement->options->options)) {
                $migrationWriter->handleDropTableStatement($statement);

                if (!$squash) {
                    $this->creator->setUpDefinition($migrationWriter->getUpDefinition());
                    $this->creator->setDownDefinition($migrationWriter->getDownDefinition());

                    $this->writeMigration(sprintf('delete_%s_table', $statement->fields[0]->table), $statement->fields[0]->table, true);
                }
                else {
                    $dropDefinitions[] = [
                        'up' => $migrationWriter->getUpDefinition(),
                        'down' => $migrationWriter->getDownDefinition()
                    ];
                }

                $migrationWriter->reset();
            }
        }

        foreach (['create' => $createDefinitions, 'update' => $alterDefinitions, 'delete' => $dropDefinitions] as $type => $definitions) {
            if (empty($definitions)) {
                continue;
            }

            $upDefinition = implode("\n", array_column($definitions, 'up'));
            $downDefinition = implode("\n", array_column($definitions, 'down'));

            $this->creator->setUpDefinition($upDefinition);
            $this->creator->setDownDefinition($downDefinition);

            $this->writeMigration(sprintf('%s_%s_database', $type, $schemaName), $schemaName, true);
        }

        $this->composer->dumpAutoloads();

        config(["database.connections.mysql.database" => $schemaName]);

        $this->info("Import SQL file $sqlImportFile into a new $schemaName migration finished successfully!");

        return Command::SUCCESS;
    }

}
