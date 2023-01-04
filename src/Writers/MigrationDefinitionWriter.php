<?php

namespace Nachopitt\Migrations\Writers;

use Illuminate\Support\Str;
use Nachopitt\Migrations\MigrationDefinition;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Token;

class MigrationDefinitionWriter {

    protected $allowedDataTypes;
    protected $columnBlueprints;
    protected $columnModifierBlueprints;
    protected $actionBlueprints;
    protected $referencesOptionBlueprints;
    protected MigrationDefinition $upDefinition;
    protected MigrationDefinition $downDefinition;

    protected const ALTER_OPERATION_ADD_COLUMN      = 0;
    protected const ALTER_OPERATION_CHANGE_COLUMN   = 1;
    protected const ALTER_OPERATION_ADD_KEY         = 2;
    protected const ALTER_OPERATION_ADD_CONSTRAINT  = 3;

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

        foreach ($this->allowedDataTypes as $allowedDataType) {
            switch ($allowedDataType) {
                case 'INT':
                case 'INTEGER':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->integer('%s')", $fieldName);
                    };
                    break;
                case 'SMALLINT':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->smallInteger('%s')", $fieldName);
                    };
                    break;
                case 'TINYINT':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->tinyInteger('%s')", $fieldName);
                    };
                    break;
                case 'MEDIUMINT':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->mediumInteger('%s')", $fieldName);
                    };
                    break;
                case 'BIGINT':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->bigInteger('%s')", $fieldName);
                    };
                    break;
                case 'DEC':
                case 'FIXED':
                case 'NUMERIC':
                case 'DECIMAL':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->decimal('%s')", $fieldName);
                    };
                    break;
                case 'FLOAT':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->float('%s')", $fieldName);
                    };
                    break;
                case 'DOUBLE':
                case 'REAL':
                case 'DOUBLE PRECISION':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->double('%s')", $fieldName);
                    };
                    break;
                case 'BIT':
                case 'BOOLEAN':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->boolean('%s')", $fieldName);
                    };
                    break;
                case 'DATE':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->date('%s')", $fieldName);
                    };
                    break;
                case 'DATETIME':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->dateTime('%s')", $fieldName);
                    };
                    break;
                case 'TIMESTAMP':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->timestamp('%s')", $fieldName);
                    };
                    break;
                case 'TIME':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->time('%s')", $fieldName);
                    };
                    break;
                case 'YEAR':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->year('%s')", $fieldName);
                    };
                    break;
                case 'CHAR':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->char('%s')", $fieldName);
                    };
                    break;
                case 'VARCHAR':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        if (empty($parameters)) {
                            return sprintf("\$table->string('%s')", $fieldName);
                        }
                        else {
                            return sprintf("\$table->string('%s', %u)", $fieldName, $parameters[0]);
                        }
                    };
                    break;
                case 'BLOB':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->binary('%s')", $fieldName);
                    };
                    break;
                case 'TINYTEXT':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->tinyText('%s')", $fieldName);
                    };
                    break;
                case 'TEXT':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->text('%s')", $fieldName);
                    };
                    break;
                case 'MEDIUMTEXT':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->mediumText('%s')", $fieldName);
                    };
                    break;
                case 'LONGTEXT':
                    $this->columnBlueprints[$allowedDataType] = function ($fieldName, $parameters = []) {
                        return sprintf("\$table->longText('%s')", $fieldName);
                    };
                    break;
            }
        }

        $this->columnModifierBlueprints = [
            'whitelist' => [
                'UNSIGNED' => function ($optionValue) {
                    return "->unsigned()";
                },
                'AUTO_INCREMENT' => function ($optionValue) {
                    return "->autoIncrement()";
                },
                'CHARACTER SET' => function ($optionValue) {
                    return sprintf("->charset('%s')", $optionValue);
                },
                'COLLATE' => function ($optionValue) {
                    return sprintf("->collation('%s')", $optionValue);
                },
                'DEFAULT' => function ($optionValue) {
                    if ($optionValue === 'NULL')
                        return "->default(NULL)";
                    else {
                        $format = '%s';
                        if (is_numeric($optionValue)) {
                            $format = '"' . $format . '"';
                        }
                        return sprintf("->default($format)", $optionValue);
                    }
                },
                'AFTER' => function ($optionValue) {
                    return sprintf("->after('%s')", $optionValue);
                },
            ],
            'blacklist' => [
                'NOT NULL' => function () {
                    return "->nullable()";
                },
            ]
        ];

        $this->actionBlueprints = [];
        $this->referencesOptionBlueprints = [];

        foreach (['RESTRICT', 'CASCADE', 'SET NULL', 'NO ACTION', 'SET DEFAULT'] as $action) {
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

        foreach (['ON DELETE', 'ON UPDATE'] as $referencesOption) {
            switch ($referencesOption) {
                case 'ON DELETE':
                case 'ON UPDATE':
                    $this->referencesOptionBlueprints[$referencesOption] = function ($action) use ($referencesOption) {
                        return sprintf("->%s('%s')", Str::camel(Str::lower(Str::replace(' ', '_', $referencesOption))), $this->actionBlueprints[$action]());
                    };
                    break;
            }
        }
    }

    public function handleCreateTableStatement(CreateStatement $statement) {
        $tableName = $statement->name->table;
        $this->upDefinition->append("Schema::create('$tableName', function (Blueprint \$table) {", false);
        $this->upDefinition->increaseIndentation();
        $this->downDefinition->append("Schema::dropIfExists('$tableName');", false);

        foreach ($statement->fields as $field) {
            if (!empty($field->name) && !empty($field->type)) {
                if (!empty($this->columnBlueprints[$field->type->name])) {
                    $this->upDefinition->append($this->columnBlueprints[$field->type->name]($field->name, $field->type->parameters));
                    $this->upDefinition->increaseIndentation();

                    $options = array_merge($field->type->options->options, $field->options->options);

                    foreach ($this->columnModifierBlueprints['whitelist'] as $whitelistOptionName => $whitelistOptionBlueprint) {
                        $present = false;
                        $optionValue = null;

                        foreach ($options as $option) {
                            $optionName = $option;

                            if (is_array($option)) {
                                $optionName = $option['name'];
                                $optionValue = $option['value'];
                            }

                            if ($optionName === $whitelistOptionName) {
                                $present = true;
                                break;
                            }
                        }

                        if ($present) {
                            $this->upDefinition->append($whitelistOptionBlueprint($optionValue));
                        }
                    }

                    foreach ($this->columnModifierBlueprints['blacklist'] as $blacklistOptionName => $blacklistOptionBlueprint) {
                        if (!in_array($blacklistOptionName, $options)) {
                            $this->upDefinition->append($blacklistOptionBlueprint());
                        }
                    }

                    $this->upDefinition->append(';', false, false);
                    $this->upDefinition->decreaseIndentation();
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
                        $this->upDefinition->increaseIndentation();

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
                                foreach ($field->references->options->options as $referencesOption) {
                                    $this->upDefinition->append($this->referencesOptionBlueprints[$referencesOption['name']]($referencesOption['value']), false, false);
                                }
                            }
                        }

                        $this->upDefinition->decreaseIndentation();
                    }

                    $this->upDefinition->append(';', false, false);
                }
            }
        }

        $this->upDefinition->decreaseIndentation();
        $this->upDefinition->append('});');
    }

    public function handleAlterTableStatement(AlterStatement $statement) {
        $tableName = $statement->table->table;
        $this->upDefinition->append("Schema::table('$tableName', function (Blueprint \$table) {", false);
        $this->upDefinition->increaseIndentation();

        foreach ($statement->altered as $alterOperation) {
            if (!array_diff($alterOperation->options->options, ['ADD', 'COLUMN'])) {
                $tokens = array_diff(array_column($alterOperation->unknown, 'value'), [' ']);
                $keys = array_keys($tokens);

                foreach ($keys as $key => $tokenKey) {
                    $token = $tokens[$tokenKey];

                    if (in_array($token, $this->allowedDataTypes)) {
                        $parameters = [];
                        $parametersKeys = array_keys(array_intersect($tokens, ['(', ')']));

                        if (!empty($parametersKeys)) {
                            $parameters = array_diff(array_slice($tokens, $parametersKeys[0] + 1, $parametersKeys[1] - $parametersKeys[0] - 1), [',']);
                        }

                        $this->upDefinition->append($this->columnBlueprints[$token]($alterOperation->field->column, $parameters));
                        $this->upDefinition->increaseIndentation();
                    }
                    else if (array_key_exists($token, $this->columnModifierBlueprints['whitelist'])) {
                        $optionValue = null;

                        if ($token == 'AFTER') {
                            $optionValue = $tokens[$keys[$key + 1]];
                        }

                        $this->upDefinition->append($this->columnModifierBlueprints['whitelist'][$token]($optionValue));
                    }
                }

                foreach ($this->columnModifierBlueprints['blacklist'] as $blacklistOptionName => $blacklistOptionBlueprint) {
                    if (!in_array($blacklistOptionName, $tokens)) {
                        $this->upDefinition->append($blacklistOptionBlueprint());
                    }
                }

                $this->upDefinition->append(';', false, false);
                $this->upDefinition->decreaseIndentation();
            }
        }

        $this->upDefinition->decreaseIndentation();
        $this->upDefinition->append('});');
    }

    protected function getAlterOperationType($options) {
        if (is_array($options)) {
            if (!array_diff($options, ['ADD'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_ADD_COLUMN;
            }
            else if (!array_diff($options, ['ADD', 'COLUMN'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_ADD_COLUMN;
            }
            else if (!array_diff($options, ['CHANGE'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_CHANGE_COLUMN;
            }
            else if (!array_diff($options, ['CHANGE', 'COLUMN'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_CHANGE_COLUMN;
            }
            else if (!array_diff($options, ['ADD', 'KEY'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_ADD_KEY;
            }
            else if (!array_diff($options, ['ADD', 'CONSTRAINT'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_ADD_CONSTRAINT;
            }
        }
        else {
            return false;
        }
    }

    public function getUpDefinition() {
        return $this->upDefinition->get();
    }

    public function getDownDefinition() {
        return $this->downDefinition->get();
    }

    public function reset() {
        $this->upDefinition = new MigrationDefinition;
        $this->downDefinition = new MigrationDefinition;
    }
}
