<?php

namespace Nachopitt\Migration;

class MigrationCreator extends \Illuminate\Database\Migrations\MigrationCreator {

    protected $definition;

    protected function setDefinition($definition) {
        $this->definition = $definition;
    }

    protected function populateStub($stub, $table)
    {
        $stub = parent::populateStub($stub, $table);

        // Here we will replace the table place-holders with the table specified by
        // the developer, which is useful for quickly creating a tables creation
        // or update migration from the console instead of typing it manually.
        if (! is_null($this->definition)) {
            $stub = str_replace(
                ['DummyDefinition', '{{ definition }}', '{{definition}}'],
                $this->definition, $stub
            );
        }

        return $stub;
    }
}
