<?php

namespace Nachopitt\Migration\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Nachopitt\Migration\MigrationDefinition;
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
        {--s|schema= : The name of the schema}
        {--path= : The location where the migration file should be created}
        {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
        {--fullpath : Output the full path of the migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration file by importing a SQL file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $defaultDatabase = config("database.connections.mysql.database");
        $schemaName = $this->option('schema') ?: $defaultDatabase;
        $sqlImportFile = $this->argument('file') ?: "database_model/${defaultDatabase}_diff.sql";

        $sqlImportFileContents = File::get($sqlImportFile);

        $parser = new Parser($sqlImportFileContents);

        foreach($parser->statements as $statement) {
            if ($statement instanceof CreateStatement && in_array('TABLE', $statement->options->options)) {
                $this->creator->setDefinition($this->handleCreateStatement($statement));
                $this->writeMigration(sprintf('create_%s_table', $statement->name->table), $statement->name->table, true);

                $this->composer->dumpAutoloads();
            }
            else if ($statement instanceof AlterStatement) {

            }
            else if ($statement instanceof DropStatement) {
                
            }
        }

        config(["database.connections.mysql.database" => $schemaName]);

        $this->info("Import SQL file $sqlImportFile into a new $schemaName migration finished successfully!");

        return Command::SUCCESS;
    }

    public function handleCreateStatement(CreateStatement $statement) {
        $definition = new MigrationDefinition;

        $allowedDataTypes = [
            'INT',
            'INTEGER',
            'SMALLINT',
            'TINYINT',
            'MEDIUMINT',
            'BIGINT',
            'DEC',
            'FIXED',
            'NUMERIC',
            'DECIMAL',
            'FLOAT',
            'DOUBLE',
            'REAL',
            'DOUBLE PRECISION',
            'BIT',
            'BOOLEAN',
            'DATE',
            'DATETIME',
            'TIMESTAMP',
            'TIME',
            'YEAR',
            'CHAR',
            'VARCHAR',
            'BLOB',
            'TEXT',
        ];

        foreach($allowedDataTypes as $allowedDataType) {
            switch ($allowedDataType) {
                case 'INT':
                case 'INTEGER':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->integer('%s')", $field->name);
                    };
                    break;
                case 'SMALLINT':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->smallInteger('%s')", $field->name);
                    };
                    break;
                case 'TINYINT':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->tinyInteger('%s')", $field->name);
                    };
                    break;
                case 'MEDIUMINT':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->mediumInteger('%s')", $field->name);
                    };
                    break;
                case 'BIGINT':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->bigInteger('%s')", $field->name);
                    };
                    break;
                case 'DEC':
                case 'FIXED':
                case 'NUMERIC':
                case 'DECIMAL':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->decimal('%s')", $field->name);
                    };
                    break;
                case 'FLOAT':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->float('%s')", $field->name);
                    };
                    break;
                case 'DOUBLE':
                case 'REAL':
                case 'DOUBLE PRECISION':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->double('%s')", $field->name);
                    };
                    break;
                case 'BIT':
                case 'BOOLEAN':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->boolean('%s')", $field->name);
                    };
                    break;
                case 'DATE':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->date('%s')", $field->name);
                    };
                    break;
                case 'DATETIME':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->dateTime('%s')", $field->name);
                    };
                    break;
                case 'TIMESTAMP':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->timestamp('%s')", $field->name);
                    };
                    break;
                case 'TIME':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->time('%s')", $field->name);
                    };
                    break;
                case 'YEAR':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->year('%s')", $field->name);
                    };
                    break;
                case 'CHAR':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->char('%s')", $field->name);
                    };
                    break;
                case 'VARCHAR':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        if (empty($field->type->parameters)) {
                            return sprintf("\$table->string('%s')", $field->name);
                        }
                        else {
                            return sprintf("\$table->string('%s', %u)", $field->name, $field->type->parameters[0]);
                        }
                    };
                    break;
                case 'BLOB':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->binary('%s')", $field->name);
                    };
                    break;
                case 'TINYTEXT':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->tinyText('%s')", $field->name);
                    };
                    break;
                case 'TEXT':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->text('%s')", $field->name);
                    };
                    break;
                case 'MEDIUMTEXT':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->mediumText('%s')", $field->name);
                    };
                    break;
                case 'LONGTEXT':
                    $columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->longText('%s')", $field->name);
                    };
                    break;
            }
        }

        $columnModifierBlueprints = [
            'whitelist' => [
                'UNSIGNED' => function ($field, $option) {
                    return "->unsigned()";
                },
                'AUTO_INCREMENT' => function ($field, $option) {
                    return "->autoIncrement()";
                },
                'COLLATE' => function ($field, $option) {
                    return sprintf("->collation('%s')", $option['value']);
                },
                'DEFAULT' => function ($field, $option) {
                    if ($option['value'] === 'NULL')
                        return "->default(NULL)";
                    else {
                        $format = '%s';
                        if (is_numeric($option['value'])) {
                            $format = '"' . $format . '"';
                        }
                        return sprintf("->default($format)", $option['value']);
                    }
                },
            ],
            'blacklist' => [
                'NOT NULL' => function ($field, $option) {
                    return "->nullable()";
                },
            ]
        ];

        $actionBlueprints = [];
        $referencesOptionBlueprints = [];

        foreach(['RESTRICT', 'CASCADE', 'SET NULL', 'NO ACTION', 'SET DEFAULT'] as $action) {
            switch ($action) {
                case 'RESTRICT':
                case 'CASCADE':
                case 'SET NULL':
                case 'NO ACTION':
                case 'SET DEFAULT':
                    $actionBlueprints[$action] = function () use ($action) {
                        return Str::lower($action);
                    };
                    break;
            }
        }

        foreach(['ON DELETE', 'ON UPDATE'] as $referencesOption) {
            switch ($referencesOption) {
                case 'ON DELETE':
                case 'ON UPDATE':
                    $referencesOptionBlueprints[$referencesOption] = function ($action) use ($referencesOption, $actionBlueprints) {
                        return sprintf("->%s('%s')", Str::camel(Str::lower(Str::replace(' ', '_', $referencesOption))), $actionBlueprints[$action]());
                    };
                    break;
            }
        }

        foreach($statement->fields as $field) {
            if (!empty($field->name) && !empty($field->type)) {
                if (!empty($columnBlueprints[$field->type->name])) {
                    $definition->append($columnBlueprints[$field->type->name]($field));
                    $definition->increaseIdentation();

                    $options = array_merge($field->type->options->options, $field->options->options);
                    foreach ($options as $option) {
                        $optionName = is_array($option) ? $option['name'] : $option;
                        if (!empty($columnModifierBlueprints['whitelist'][$optionName])) {
                            $definition->append($columnModifierBlueprints['whitelist'][$optionName]($field, $option));
                        }
                    }

                    foreach ($columnModifierBlueprints['blacklist'] as $blacklistOptionName => $blacklistOption) {
                        $found = false;
                        foreach ($options as $option) {
                            $optionName = is_array($option) ? $option['name'] : $option;
                            if ($optionName === $blacklistOptionName) {
                                $found = true;
                            }
                        }

                        if (!$found) {
                            $definition->append($blacklistOption($field, $option));
                        }
                    }
    
                    $definition->append(';', false, false);
                    $definition->decreaseIdentation();
                }
            }
            if (!empty($field->key)) {
                if ($field->key->type === 'PRIMARY KEY') {
                }

                if ($field->key->type  === 'KEY') {
                    if (count($field->key->columns) == 1) {
                        $definition->append(sprintf("\$table->index('%s', '%s')", $field->key->columns[0]['name'], $field->key->name));
                    }
                    else {
                        $references = array_column($field->key->columns, 'name');
                        $definition->append(sprintf("\$table->index(['" . implode("', '", $references) . "'], '%s')", $field->key->name));
                    }

                    $definition->append(';', false, false);
                }

                if ($field->key->type  === 'FOREIGN KEY') {
                    if (count($field->key->columns) == 1) {
                        $definition->append(sprintf("\$table->foreign('%s', '%s')", $field->key->columns[0]['name'], $field->name));
                    }
                    else {
                        $references = array_column($field->key->columns, 'name');
                        $definition->append(sprintf("\$table->foreign(['" . implode("', '", $references) . "'], '%s')", $field->key->name));
                    }

                    if (!empty($field->references)) {
                        $definition->increaseIdentation();

                        if (!empty($field->references->columns)) {
                            if (count($field->references->columns) == 1) {
                                $definition->append(sprintf("->references('%s')", $field->references->columns[0]));
                            }
                            else {
                                $definition->append("->references(['" . implode("', '", $field->references->columns) . "'])");
                            }
                        }

                        if (!empty($field->references->columns)) {
                            $definition->append(sprintf("->on('%s')", $field->references->table->table));
                        }

                        if (!empty($field->references->options)) {
                            if (!empty($field->references->options->options)) {
                                foreach($field->references->options->options as $referencesOption) {
                                    $definition->append($referencesOptionBlueprints[$referencesOption['name']]($referencesOption['value']), false, false);
                                }
                            }
                        }

                        $definition->decreaseIdentation();
                    }

                    $definition->append(';', false, false);
                }
            }
        }
        
        return $definition->get();
    }
}
