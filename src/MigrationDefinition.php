<?php

namespace Nachopitt\Migrations;

class MigrationDefinition {

    private $definition;
    private $indentation;

    private const TAB_SIZE = 4;

    public function __construct() {
        $this->definition = '';
        $this->indentation = 2;
    }

    public function setIndentation($indentation) {
        $this->indentation = $indentation;
    }

    public function increaseIndentation($indentation = 1) {
        $this->indentation += $indentation;
    }

    public function decreaseIndentation($indentation = 1) {
        $this->indentation -= $indentation;
    }

    public function append($definition, $newLine = true, $indent = true) {
        if ($newLine) {
            $this->definition .= "\n";
        }

        if ($indent) {
            $this->definition .= str_repeat(' ', $this->indentation * MigrationDefinition::TAB_SIZE);
        }

        $this->definition .= $definition;
    }

    public function get() {
        return $this->definition;
    }
}
