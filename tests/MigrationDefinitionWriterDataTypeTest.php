<?php

namespace Nachopitt\Migrations\Tests;

use Nachopitt\Migrations\Writers\MigrationDefinitionWriter;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MigrationDefinitionWriterDataTypeTest extends TestCase
{
    #[DataProvider('columnTypeProvider')]
    public function test_maps_supported_mysql_column_types(string $columnDefinition, array $expectedBlueprints): void
    {
        $writer = new MigrationDefinitionWriter();
        $statement = $this->parseCreateStatement($columnDefinition);

        $writer->handleCreateTableStatement($statement);

        $generated = $writer->getUpDefinition();

        foreach ($expectedBlueprints as $expectedBlueprint) {
            $this->assertStringContainsString($expectedBlueprint, $generated);
        }
    }

    public static function columnTypeProvider(): array
    {
        return [
            'int' => ['`sample` INT NOT NULL', ["\$table->integer('sample');"]],
            'integer' => ['`sample` INTEGER NOT NULL', ["\$table->integer('sample');"]],
            'smallint' => ['`sample` SMALLINT NOT NULL', ["\$table->smallInteger('sample');"]],
            'tinyint' => ['`sample` TINYINT NOT NULL', ["\$table->tinyInteger('sample');"]],
            'mediumint' => ['`sample` MEDIUMINT NOT NULL', ["\$table->mediumInteger('sample');"]],
            'bigint unsigned' => ['`sample` BIGINT UNSIGNED NOT NULL', ["\$table->bigInteger('sample')", '->unsigned();']],
            'decimal' => ['`sample` DECIMAL(8,2) NOT NULL', ["\$table->decimal('sample');"]],
            'float' => ['`sample` FLOAT(10,2) NOT NULL', ["\$table->float('sample');"]],
            'double' => ['`sample` DOUBLE(10,2) NOT NULL', ["\$table->double('sample');"]],
            'bit' => ['`sample` BIT NOT NULL', ["\$table->boolean('sample');"]],
            'boolean' => ['`sample` BOOLEAN NOT NULL', ["\$table->boolean('sample');"]],
            'date' => ['`sample` DATE NOT NULL', ["\$table->date('sample');"]],
            'datetime' => ['`sample` DATETIME NOT NULL', ["\$table->dateTime('sample');"]],
            'timestamp nullable' => ['`sample` TIMESTAMP NULL', ["\$table->timestamp('sample')", '->nullable();']],
            'time' => ['`sample` TIME NOT NULL', ["\$table->time('sample');"]],
            'year' => ['`sample` YEAR NOT NULL', ["\$table->year('sample');"]],
            'char' => ['`sample` CHAR(12) NOT NULL', ["\$table->char('sample');"]],
            'varchar' => ['`sample` VARCHAR(255) NOT NULL', ["\$table->string('sample', 255);"]],
            'blob' => ['`sample` BLOB NOT NULL', ["\$table->binary('sample');"]],
            'tinytext' => ['`sample` TINYTEXT NOT NULL', ["\$table->tinyText('sample');"]],
            'text' => ['`sample` TEXT NOT NULL', ["\$table->text('sample');"]],
            'mediumtext' => ['`sample` MEDIUMTEXT NOT NULL', ["\$table->mediumText('sample');"]],
            'longtext' => ['`sample` LONGTEXT NOT NULL', ["\$table->longText('sample');"]],
        ];
    }

    private function parseCreateStatement(string $columnDefinition): CreateStatement
    {
        $sql = <<<SQL
CREATE TABLE `type_examples` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  {$columnDefinition},
  PRIMARY KEY (`id`)
)
SQL;

        $parser = new Parser($sql);
        $statement = $parser->statements[0] ?? null;

        $this->assertInstanceOf(CreateStatement::class, $statement);

        return $statement;
    }
}
