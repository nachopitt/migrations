<?php

namespace Nachopitt\Migrations;

class MigrationCreator extends \Illuminate\Database\Migrations\MigrationCreator {

    protected $upDefinition;

    public function setUpDefinition($upDefinition) {
        $this->upDefinition = $upDefinition;
    }

    /**
     * Get the migration stub file.
     *
     * @param  string|null  $table
     * @param  bool  $create
     * @return string
     */
    protected function getStub($table, $create)
    {
        if ($create) {
            $stub = $this->files->exists($customPath = $this->customStubPath.'/migration.create.stub')
                            ? $customPath
                            : __DIR__.'/stubs/migration.create.stub';

            return $this->files->get($stub);
        }
        else {
            return parent::getStub($table, $create);
        }
    }

    protected function populateStub($stub, $table)
    {
        $stub = parent::populateStub($stub, $table);

        // Here we will replace the table place-holders with the table specified by
        // the developer, which is useful for quickly creating a tables creation
        // or update migration from the console instead of typing it manually.
        if (!is_null($this->upDefinition)) {
            $stub = str_replace(
                ['DummyUpDefinition', '{{ upDefinition }}', '{{upDefinition}}'],
                $this->upDefinition, $stub
            );
        }

        return $stub;
    }
}
