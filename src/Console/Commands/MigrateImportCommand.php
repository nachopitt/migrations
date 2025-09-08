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
        {--squash : Generate one migration file instead of multiple files}
        {--withoutForeignKeyConstraints : Do not include foreign key constraints in the migration}';

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
        $sqlImportFile = $this->argument('file') ?: "database_model/" . $defaultDatabase . ".sql";
        $squash = $this->option('squash');
        $withoutForeignKeyConstraints = $this->option('withoutForeignKeyConstraints');

        $sqlImportFileContents = File::get($sqlImportFile);

        $parser = new Parser($sqlImportFileContents);
        $migrationWriter = new MigrationDefinitionWriter;

        $createStatements = [];
        $alterStatements = [];
        $dropStatements = [];

        foreach($parser->statements as $key => $statement) {
            if ($statement instanceof CreateStatement && in_array('TABLE', $statement->options->options)) {
                $createStatements[] = $statement;
            }
            else if ($statement instanceof AlterStatement && in_array('TABLE', $statement->options->options)) {
                $alterStatements[] = $statement;
            }
            else if ($statement instanceof DropStatement && in_array('TABLE', $statement->options->options)) {
                $dropStatements[] = $statement;
            }
        }

        $allStatements = [
            'create' => $createStatements,
            'update' => $alterStatements,
            'delete' => $dropStatements
        ];

        foreach ($allStatements as $type => $statements) {
            if (empty($statements)) {
                continue;
            }

            if (!$squash) {
                foreach ($statements as $statement) {
                    $migrationWriter->reset();

                    if ($withoutForeignKeyConstraints) {
                        $migrationWriter->beginWithoutForeignKeyConstraints();
                    }

                    $name = '';
                    if ($statement instanceof CreateStatement) {
                        $migrationWriter->handleCreateTableStatement($statement);
                        $name = $statement->name->table;
                    } else if ($statement instanceof AlterStatement) {
                        $migrationWriter->handleAlterTableStatement($statement);
                        $name = $statement->table->table;
                    } else if ($statement instanceof DropStatement) {
                        $migrationWriter->handleDropTableStatement($statement);
                        $name = $statement->fields[0]->table;
                    }

                    if ($withoutForeignKeyConstraints) {
                        $migrationWriter->endWithoutForeignKeyConstraints();
                    }

                    $this->creator->setUpDefinition($migrationWriter->getUpDefinition());
                    $this->creator->setDownDefinition($migrationWriter->getDownDefinition());

                    $this->writeMigration(sprintf('%s_%s_table', $type, $name), $name, true);
                }
            }
            else {
                $migrationWriter->reset();

                if ($withoutForeignKeyConstraints && !empty($statements)) {
                    $migrationWriter->beginWithoutForeignKeyConstraints();
                }

                foreach ($statements as $statement) {
                    if ($statement instanceof CreateStatement) {
                        $migrationWriter->handleCreateTableStatement($statement);
                    } else if ($statement instanceof AlterStatement) {
                        $migrationWriter->handleAlterTableStatement($statement);
                    } else if ($statement instanceof DropStatement) {
                        $migrationWriter->handleDropTableStatement($statement);
                    }
                }

                if ($withoutForeignKeyConstraints && !empty($statements)) {
                    $migrationWriter->endWithoutForeignKeyConstraints();
                }

                $this->creator->setUpDefinition($migrationWriter->getUpDefinition());
                $this->creator->setDownDefinition($migrationWriter->getDownDefinition());

                $this->writeMigration(sprintf('%s_%s_database', $type, $schemaName), $schemaName, true);
            }
        }

        config(["database.connections.mysql.database" => $schemaName]);

        $this->info("Import SQL file $sqlImportFile into a new $schemaName migration finished successfully!");

        return Command::SUCCESS;
    }
}
