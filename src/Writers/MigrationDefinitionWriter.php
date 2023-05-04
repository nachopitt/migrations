<?php

namespace Nachopitt\Migrations\Writers;

use Illuminate\Support\Str;
use Nachopitt\Migrations\MigrationDefinition;
use Nachopitt\Migrations\Writers\MigrationDefinitionWriter as WritersMigrationDefinitionWriter;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PhpMyAdmin\SqlParser\Token;

class MigrationDefinitionWriter {

    protected $allowedDataTypes;
    protected $columnBlueprints;
    protected $columnModifierBlueprints;
    protected $actionBlueprints;
    protected $referencesOptionBlueprints;
    protected MigrationDefinition $upDefinition;
    protected MigrationDefinition $downDefinition;

    protected const ALTER_OPERATION_ADD_COLUMN          = 0;
    protected const ALTER_OPERATION_CHANGE_COLUMN       = 1;
    protected const ALTER_OPERATION_ADD_INDEX           = 2;
    protected const ALTER_OPERATION_ADD_UNIQUE          = 3;
    protected const ALTER_OPERATION_ADD_FULLTEXT        = 4;
    protected const ALTER_OPERATION_ADD_CONSTRAINT      = 5;
    protected const ALTER_OPERATION_DROP_COLUMN         = 6;
    protected const ALTER_OPERATION_DROP_INDEX          = 7;
    protected const ALTER_OPERATION_DROP_FOREIGN_KEY    = 8;
    protected const ALTER_OPERATION_RENAME_TABLE        = 9;
    protected const ALTER_OPERATION_RENAME_COLUMN       = 10;
    protected const ALTER_OPERATION_RENAME_INDEX        = 11;

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
        $this->upDefinition->append($this->createTableBlueprint($tableName));
        $this->upDefinition->increaseIndentation();

        $this->downDefinition->append($this->dropTableBlueprint($tableName));

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

                if ($field->key->type === 'INDEX') {
                    $this->upDefinition->append($this->keyBlueprint('index', array_column($field->key->columns, 'name'), $field->key->name));
                    $this->upDefinition->append(';', false, false);
                }

                if ($field->key->type  === 'FOREIGN KEY') {
                    $this->upDefinition->append($this->keyBlueprint('foreign', array_column($field->key->columns, 'name'), $field->name));

                    if (!empty($field->references)) {
                        $this->upDefinition->increaseIndentation();

                        if (!empty($field->references->columns)) {
                            $this->upDefinition->append($this->genericBlueprint('references', $field->references->columns));
                        }

                        if (!empty($field->references->table->table)) {
                            $this->upDefinition->append($this->onBlueprint($field->references->table->table));
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
        $this->upDefinition->append($this->alterTableBlueprint($tableName));
        $this->upDefinition->increaseIndentation();

        $this->downDefinition->append($this->alterTableBlueprint($tableName));
        $this->downDefinition->increaseIndentation();

        foreach ($statement->altered as $alterOperation) {
            $tokens = array_values(array_diff(array_column($alterOperation->unknown, 'value'), [' ']));
            $alterOperationType = $this->getAlterOperationType($alterOperation->options->options, $tokens);

            switch ($alterOperationType) {
                case MigrationDefinitionWriter::ALTER_OPERATION_ADD_COLUMN:
                case MigrationDefinitionWriter::ALTER_OPERATION_CHANGE_COLUMN:
                    foreach ($tokens as $tokenKey => $token) {
                        if (in_array(Str::upper($token), $this->allowedDataTypes)) {
                            $parameters = $this->getParameters($tokens);

                            $this->upDefinition->append($this->columnBlueprints[Str::upper($token)]($alterOperation->field->column, $parameters));
                            $this->upDefinition->increaseIndentation();
                        }
                        else if (array_key_exists($token, $this->columnModifierBlueprints['whitelist'])) {
                            $optionValue = null;

                            switch ($token) {
                                case 'CHARACTER SET':
                                case 'COLLATE':
                                case 'DEFAULT':
                                case 'AFTER':
                                    $optionValue = $tokens[$tokenKey + 1];
                                    break;
                            }

                            $this->upDefinition->append($this->columnModifierBlueprints['whitelist'][$token]($optionValue));
                        }
                    }

                    foreach ($this->columnModifierBlueprints['blacklist'] as $blacklistOptionName => $blacklistOptionBlueprint) {
                        if (!in_array($blacklistOptionName, $tokens)) {
                            $this->upDefinition->append($blacklistOptionBlueprint());
                        }
                    }

                    if ($alterOperationType === MigrationDefinitionWriter::ALTER_OPERATION_CHANGE_COLUMN) {
                        $this->upDefinition->append($this->changeBlueprint());
                    }

                    $this->upDefinition->append(';', false, false);
                    $this->upDefinition->decreaseIndentation();

                    if ($alterOperationType === MigrationDefinitionWriter::ALTER_OPERATION_CHANGE_COLUMN) {
                        if ($alterOperation->field->column !== $tokens[0]) {
                            $this->upDefinition->append($this->renameBlueprint('column', $alterOperation->field->column, $tokens[0]));
                            $this->upDefinition->append(';', false, false);
                        }

                        $this->downDefinition->append('// Revert manually CHANGE COLUMN ' . $alterOperation->field->column . ' alter operation to the previous definition.');
                    }
                    else {
                        $this->downDefinition->append($this->dropBlueprint('column', $alterOperation->field->column));
                        $this->downDefinition->append(';', false, false);
                    }

                    break;
                case MigrationDefinitionWriter::ALTER_OPERATION_ADD_INDEX:
                case MigrationDefinitionWriter::ALTER_OPERATION_ADD_UNIQUE:
                case MigrationDefinitionWriter::ALTER_OPERATION_ADD_FULLTEXT:
                    $fields = $this->getParameters($tokens);

                    $keyBlueprintType = 'index';
                    if ($alterOperationType === MigrationDefinitionWriter::ALTER_OPERATION_ADD_UNIQUE) {
                        $keyBlueprintType = 'unique';
                    }
                    else if ($alterOperationType === MigrationDefinitionWriter::ALTER_OPERATION_ADD_FULLTEXT) {
                        $keyBlueprintType = 'fullText';
                    }

                    $table = $alterOperation->field ? $alterOperation->field->column : $tokens[0];

                    $this->upDefinition->append($this->keyBlueprint($keyBlueprintType, $fields, $table));
                    $this->upDefinition->append(';', false, false);

                    $this->downDefinition->append($this->dropBlueprint($keyBlueprintType, $table));
                    $this->downDefinition->append(';', false, false);

                    break;
                case MigrationDefinitionWriter::ALTER_OPERATION_ADD_CONSTRAINT:
                    foreach ($tokens as $tokenKey => $token) {
                        if ($token == 'FOREIGN KEY') {
                            $fields = $this->getParameters(array_slice($tokens, $tokenKey + 1));
                            $this->upDefinition->append($this->keyBlueprint('foreign', $fields, $alterOperation->field->column));
                            $this->upDefinition->increaseIndentation();
                        }
                        else if ($token == 'REFERENCES') {
                            $references = $this->getParameters(array_slice($tokens, $tokenKey + 2));
                            $this->upDefinition->append($this->genericBlueprint('references', $references));
                            $this->upDefinition->append($this->onBlueprint($tokens[$tokenKey + 1]), false, false);
                        }
                        else if (array_key_exists($token, $this->referencesOptionBlueprints)) {
                            $this->upDefinition->append($this->referencesOptionBlueprints[$token]($tokens[$tokenKey + 1]));
                        }
                    }

                    $this->upDefinition->append(';', false, false);
                    $this->upDefinition->decreaseIndentation();

                    $this->downDefinition->append($this->dropBlueprint('foreign', $alterOperation->field->column));
                    $this->downDefinition->append(';', false, false);

                    break;
                case MigrationDefinitionWriter::ALTER_OPERATION_RENAME_TABLE:
                    $table = $alterOperation->field ? $alterOperation->field->column : $tokens[0];
                    $this->upDefinition->append($this->renameToBlueprint($table));
                    $this->upDefinition->append(';', false, false);

                    $this->downDefinition->append($this->renameToBlueprint($tableName));
                    $this->downDefinition->append(';', false, false);

                    break;
                case MigrationDefinitionWriter::ALTER_OPERATION_RENAME_COLUMN:
                case MigrationDefinitionWriter::ALTER_OPERATION_RENAME_INDEX:
                    foreach ($tokens as $tokenKey => $token) {
                        if ($token === 'TO') {
                            $this->upDefinition->append($this->renameBlueprint('index', $alterOperation->field->column, $tokens[$tokenKey + 1]));
                            $this->upDefinition->append(';', false, false);

                            $this->downDefinition->append($this->renameBlueprint('index', $tokens[$tokenKey + 1], $alterOperation->field->column));
                            $this->downDefinition->append(';', false, false);
                        }
                    }

                    break;
                case MigrationDefinitionWriter::ALTER_OPERATION_DROP_COLUMN:
                case MigrationDefinitionWriter::ALTER_OPERATION_DROP_INDEX:
                case MigrationDefinitionWriter::ALTER_OPERATION_DROP_FOREIGN_KEY:
                    $blueprintType = 'column';
                    if (MigrationDefinitionWriter::ALTER_OPERATION_DROP_INDEX) {
                        $blueprintType = 'index';
                    }
                    else if (MigrationDefinitionWriter::ALTER_OPERATION_DROP_FOREIGN_KEY) {
                        $blueprintType = 'foreign';
                    }

                    $this->upDefinition->append($this->dropBlueprint($blueprintType, $alterOperation->field->column));
                    $this->upDefinition->append(';', false, false);

                    $downComment = '// Revert manually DROP %s %s alter operation to the previous definition.';
                    $this->downDefinition->append(sprintf($downComment, strtoupper($blueprintType), $alterOperation->field->column));

                    break;
            }
        }

        $this->upDefinition->decreaseIndentation();
        $this->upDefinition->append('});');

        $this->downDefinition->decreaseIndentation();
        $this->downDefinition->append('});');
    }

    public function handleDropTableStatement(DropStatement $statement) {
        $tables = array_column($statement->fields, 'table');

        foreach($tables as $table) {
            $this->upDefinition->append($this->dropTableBlueprint($table));

            $downComment = '// Revert manually DROP TABLE %s statement to the previous definition.';
            $this->downDefinition->append(sprintf($downComment, $table));
        }
    }

    protected function getParameters($tokens) {
        $parameters = [];
        $keys = array_keys(array_intersect($tokens, ['(', ')']));

        if (!empty($keys)) {
            $parameters = array_diff(array_slice($tokens, $keys[0] + 1, $keys[1] - $keys[0] - 1), [',']);
        }

        return $parameters;
    }

    protected function getAlterOperationType($options, &$tokens) {
        if (is_array($options)) {
            if (!array_diff($options, ['ADD'])) {
                if (!empty($tokens) && in_array($tokens[0], ['UNIQUE', 'UNIQUE INDEX', 'UNIQUE KEY'])) {
                    array_shift($tokens);

                    return MigrationDefinitionWriter::ALTER_OPERATION_ADD_UNIQUE;
                }
                else if (!empty($tokens) && in_array($tokens[0], ['FULLTEXT', 'FULLTEXT INDEX', 'FULLTEXT KEY'])) {
                    array_shift($tokens);

                    return MigrationDefinitionWriter::ALTER_OPERATION_ADD_FULLTEXT;
                }

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
            else if (!array_diff($options, ['ADD', 'INDEX'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_ADD_INDEX;
            }
            else if (!array_diff($options, ['ADD', 'KEY'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_ADD_INDEX;
            }
            else if (!array_diff($options, ['ADD', 'FULLTEXT'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_ADD_FULLTEXT;
            }
            else if (!array_diff($options, ['ADD', 'CONSTRAINT'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_ADD_CONSTRAINT;
            }
            else if (!array_diff($options, ['DROP'])) {
                if (!empty($tokens) && in_array($tokens[0], ['FOREIGN KEY'])) {
                    array_shift($tokens);

                    return MigrationDefinitionWriter::ALTER_OPERATION_ADD_UNIQUE;
                }

                return MigrationDefinitionWriter::ALTER_OPERATION_DROP_COLUMN;
            }
            else if (!array_diff($options, ['DROP', 'COLUMN'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_DROP_COLUMN;
            }
            else if (!array_diff($options, ['RENAME']) && !empty($tokens) && in_array($tokens[0], ['AS'])) {
                array_shift($tokens);

                return MigrationDefinitionWriter::ALTER_OPERATION_RENAME_TABLE;
            }
            else if (!array_diff($options, ['RENAME', 'TO'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_RENAME_TABLE;
            }
            else if (!array_diff($options, ['RENAME', 'COLUMN'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_RENAME_COLUMN;
            }
            else if (!array_diff($options, ['RENAME', 'INDEX'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_RENAME_INDEX;
            }
            else if (!array_diff($options, ['RENAME', 'KEY'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_RENAME_INDEX;
            }
            else if (!array_diff($options, ['DROP', 'INDEX'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_DROP_INDEX;
            }
            else if (!array_diff($options, ['DROP', 'KEY'])) {
                return MigrationDefinitionWriter::ALTER_OPERATION_DROP_INDEX;
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    protected function createTableBlueprint($tableName) {
        return "Schema::create('$tableName', function (Blueprint \$table) {";
    }

    protected function alterTableBlueprint($tableName) {
        return "Schema::table('$tableName', function (Blueprint \$table) {";
    }

    protected function dropTableBlueprint($tableName, $ifExists = true) {
        return sprintf("Schema::drop%s('%s');", ($ifExists ? 'IfExists' : ''), $tableName);
    }

    protected function keyBlueprint($type, $fieldNames, $indexName = null) {
        $types = ['primary', 'index', 'unique', 'fullText', 'foreign'];
        $indexName = $indexName !== null ? "'$indexName'" : 'null';

        if (in_array($type, $types)) {
            if (is_array($fieldNames) && count($fieldNames) > 1) {
                return sprintf("\$table->%s(['%s'], %s)", $type, implode("', '", $fieldNames), $indexName);
            }
            else {
                $fieldName = '';
                if (is_string($fieldNames)) {
                    $fieldName = $fieldNames;
                }
                else if (count($fieldNames) === 1) {
                    $fieldName = $fieldNames[0];
                }

                return sprintf("\$table->%s('%s', %s)", $type, $fieldName, $indexName);
            }
        }

        return false;
    }

    protected function genericBlueprint($blueprint, $parameters) {
        $blueprints = ['references', 'dropColumn', 'dropIndex', 'dropUnique', 'dropFullText', 'dropForeign'];

        if (in_array($blueprint, $blueprints)) {
            if (is_array($parameters) && count($parameters) > 1) {
                return sprintf("->%s(['%s'])", $blueprint, implode("', '", $parameters));
            }
            else {
                $parameter = '';
                if (is_string($parameters)) {
                    $parameter = $parameters;
                }
                else if (count($parameters) === 1) {
                    $parameter = $parameters[0];
                }

                return sprintf("->%s('%s')", $blueprint, $parameter);
            }
        }

        return false;
    }

    protected function dropBlueprint($type, $key) {
        return "\$table" . $this->genericBlueprint('drop' . ucfirst($type), $key);
    }

    protected function onBlueprint($tableName) {
        return sprintf("->on('%s')", $tableName);
    }

    protected function changeBlueprint() {
        return '->change()';
    }

    protected function renameBlueprint($blueprint, $oldName, $newName) {
        $blueprints = ['column', 'index'];
        if (in_array($blueprint, $blueprints)) {
            return sprintf("\$table->rename%s('%s', '%s')", Str::ucfirst($blueprint), $oldName, $newName);
        }

        return false;
    }

    protected function renameTableBlueprint($oldName, $newName) {
        return sprintf("Schema::rename('%s', '%s');", $oldName, $newName);
    }

    protected function renameToBlueprint($newName) {
        return sprintf("\$table->rename('%s')", $newName);
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
