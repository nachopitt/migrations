<?php

namespace Nachopitt\Migrations\Writers;

use Illuminate\Support\Str;
use Nachopitt\Migrations\MigrationDefinition;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;

class MigrationDefinitionWriter {

    protected $allowedDataTypes;
    protected $columnBlueprints;
    protected $columnModifierBlueprints;
    protected $actionBlueprints;
    protected $referencesOptionBlueprints;
    protected MigrationDefinition $upDefinition;
    protected MigrationDefinition $downDefinition;

    public function __construct()
    {
        $this->upDefinition = new MigrationDefinition;
        $this->downDefinition = new MigrationDefinition;

        $this->allowedDataTypes = [
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

        foreach($this->allowedDataTypes as $allowedDataType) {
            switch ($allowedDataType) {
                case 'INT':
                case 'INTEGER':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->integer('%s')", $field->name);
                    };
                    break;
                case 'SMALLINT':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->smallInteger('%s')", $field->name);
                    };
                    break;
                case 'TINYINT':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->tinyInteger('%s')", $field->name);
                    };
                    break;
                case 'MEDIUMINT':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->mediumInteger('%s')", $field->name);
                    };
                    break;
                case 'BIGINT':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->bigInteger('%s')", $field->name);
                    };
                    break;
                case 'DEC':
                case 'FIXED':
                case 'NUMERIC':
                case 'DECIMAL':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->decimal('%s')", $field->name);
                    };
                    break;
                case 'FLOAT':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->float('%s')", $field->name);
                    };
                    break;
                case 'DOUBLE':
                case 'REAL':
                case 'DOUBLE PRECISION':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->double('%s')", $field->name);
                    };
                    break;
                case 'BIT':
                case 'BOOLEAN':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->boolean('%s')", $field->name);
                    };
                    break;
                case 'DATE':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->date('%s')", $field->name);
                    };
                    break;
                case 'DATETIME':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->dateTime('%s')", $field->name);
                    };
                    break;
                case 'TIMESTAMP':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->timestamp('%s')", $field->name);
                    };
                    break;
                case 'TIME':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->time('%s')", $field->name);
                    };
                    break;
                case 'YEAR':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->year('%s')", $field->name);
                    };
                    break;
                case 'CHAR':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->char('%s')", $field->name);
                    };
                    break;
                case 'VARCHAR':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        if (empty($field->type->parameters)) {
                            return sprintf("\$table->string('%s')", $field->name);
                        }
                        else {
                            return sprintf("\$table->string('%s', %u)", $field->name, $field->type->parameters[0]);
                        }
                    };
                    break;
                case 'BLOB':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->binary('%s')", $field->name);
                    };
                    break;
                case 'TINYTEXT':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->tinyText('%s')", $field->name);
                    };
                    break;
                case 'TEXT':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->text('%s')", $field->name);
                    };
                    break;
                case 'MEDIUMTEXT':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->mediumText('%s')", $field->name);
                    };
                    break;
                case 'LONGTEXT':
                    $this->columnBlueprints[$allowedDataType] = function ($field) {
                        return sprintf("\$table->longText('%s')", $field->name);
                    };
                    break;
            }
        }

        $this->columnModifierBlueprints = [
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

        $this->actionBlueprints = [];
        $this->referencesOptionBlueprints = [];

        foreach(['RESTRICT', 'CASCADE', 'SET NULL', 'NO ACTION', 'SET DEFAULT'] as $action) {
            switch ($action) {
                case 'RESTRICT':
                case 'CASCADE':
                case 'SET NULL':
                case 'NO ACTION':
                case 'SET DEFAULT':
                    $this->actionBlueprints[$action] = function () use ($action) {
                        return Str::lower($action);
                    };
                    break;
            }
        }

        foreach(['ON DELETE', 'ON UPDATE'] as $referencesOption) {
            switch ($referencesOption) {
                case 'ON DELETE':
                case 'ON UPDATE':
                    $actionBlueprint = $this->actionBlueprints[$action];
                    $this->referencesOptionBlueprints[$referencesOption] = function ($action) use ($referencesOption, $actionBlueprint) {
                        return sprintf("->%s('%s')", Str::camel(Str::lower(Str::replace(' ', '_', $referencesOption))), $actionBlueprint());
                    };
                    break;
            }
        }
    }

    public function handleCreateTableStatement(CreateStatement $statement) {
        $tableName = $statement->name->table;
        $this->upDefinition->append("Schema::create('$tableName', function (Blueprint \$table) {");
        $this->downDefinition->append("Schema::dropIfExists('$tableName');");

        foreach($statement->fields as $field) {
            if (!empty($field->name) && !empty($field->type)) {
                if (!empty($this->columnBlueprints[$field->type->name])) {
                    $this->upDefinition->append($this->columnBlueprints[$field->type->name]($field));
                    $this->upDefinition->increaseIdentation();

                    $options = array_merge($field->type->options->options, $field->options->options);
                    foreach ($options as $option) {
                        $optionName = is_array($option) ? $option['name'] : $option;
                        if (!empty($this->columnModifierBlueprints['whitelist'][$optionName])) {
                            $this->upDefinition->append($this->columnModifierBlueprints['whitelist'][$optionName]($field, $option));
                        }
                    }

                    foreach ($this->columnModifierBlueprints['blacklist'] as $blacklistOptionName => $blacklistOption) {
                        $found = false;
                        foreach ($options as $option) {
                            $optionName = is_array($option) ? $option['name'] : $option;
                            if ($optionName === $blacklistOptionName) {
                                $found = true;
                            }
                        }

                        if (!$found) {
                            $this->upDefinition->append($blacklistOption($field, $option));
                        }
                    }

                    $this->upDefinition->append(';', false, false);
                    $this->upDefinition->decreaseIdentation();
                }
            }
            if (!empty($field->key)) {
                if ($field->key->type === 'PRIMARY KEY') {
                }

                if ($field->key->type  === 'KEY') {
                    if (count($field->key->columns) == 1) {
                        $this->upDefinition->append(sprintf("\$table->index('%s', '%s')", $field->key->columns[0]['name'], $field->key->name));
                    }
                    else {
                        $references = array_column($field->key->columns, 'name');
                        $this->upDefinition->append(sprintf("\$table->index(['" . implode("', '", $references) . "'], '%s')", $field->key->name));
                    }

                    $this->upDefinition->append(';', false, false);
                }

                if ($field->key->type  === 'FOREIGN KEY') {
                    if (count($field->key->columns) == 1) {
                        $this->upDefinition->append(sprintf("\$table->foreign('%s', '%s')", $field->key->columns[0]['name'], $field->name));
                    }
                    else {
                        $references = array_column($field->key->columns, 'name');
                        $this->upDefinition->append(sprintf("\$table->foreign(['" . implode("', '", $references) . "'], '%s')", $field->key->name));
                    }

                    if (!empty($field->references)) {
                        $this->upDefinition->increaseIdentation();

                        if (!empty($field->references->columns)) {
                            if (count($field->references->columns) == 1) {
                                $this->upDefinition->append(sprintf("->references('%s')", $field->references->columns[0]));
                            }
                            else {
                                $this->upDefinition->append("->references(['" . implode("', '", $field->references->columns) . "'])");
                            }
                        }

                        if (!empty($field->references->columns)) {
                            $this->upDefinition->append(sprintf("->on('%s')", $field->references->table->table));
                        }

                        if (!empty($field->references->options)) {
                            if (!empty($field->references->options->options)) {
                                foreach($field->references->options->options as $referencesOption) {
                                    $this->upDefinition->append($this->referencesOptionBlueprints[$referencesOption['name']]($referencesOption['value']), false, false);
                                }
                            }
                        }

                        $this->upDefinition->decreaseIdentation();
                    }

                    $this->upDefinition->append(';', false, false);
                }
            }
        }

        $this->upDefinition->decreaseIdentation();
        $this->upDefinition->append('});');
    }

    public function handleAlterTableStatement(AlterStatement $statement) {
        $tableName = $statement->table->table;
        $this->upDefinition->append("Schema::table('$tableName', function (Blueprint \$table) {");

        foreach($statement->altered as $alterOperation) {
            if (!array_diff($alterOperation->options->options, ['ADD', 'COLUMN'])) {
                $this->upDefinition->append(sprintf("\$table->%s('%s');", 'integer', $alterOperation->field->column));
            }
        }

        $this->upDefinition->append('});');
    }

    public function getUpDefinition() {
        return $this->upDefinition;
    }

    public function getDownDefinition() {
        return $this->downDefinition;
    }

    public function reset() {
        $this->upDefinition = new MigrationDefinition;
        $this->downDefinition = new MigrationDefinition;
    }
}
