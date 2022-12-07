<?php

namespace Nachopitt\Migrations;

class MigrationDefinition {

    private $definition;
    private $indentation;

    public function __construct() {
        $this->definition = '';
        $this->indentation = 3;
    }

    public function setIndentation($indentation) {
        $this->indentation = $indentation;
    }

    public function increaseIdentation($indentation = 1) {
        $this->indentation += $indentation;
    }

    public function decreaseIdentation($indentation = 1) {
        $this->indentation -= $indentation;
    }

    public function append($definition, $newLine = true, $indent = true) {
        if (!empty($this->definition)) {
            if ($newLine) {
                $this->definition .= "\r";
            }

            if ($indent) {
                for ($i = 0; $i < $this->indentation; $i++) {
                    $this->definition .= "\t";
                }
            }
        }

        $this->definition .= $definition;
    }

    public function get() {
        return $this->definition;
    }
}
