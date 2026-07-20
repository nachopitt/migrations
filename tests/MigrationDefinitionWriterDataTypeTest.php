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
        $writer = new MigrationDefinitionWriter;
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
            'json' => ['`sample` JSON NOT NULL', ["\$table->json('sample');"]],
        ];
    }

    public function test_generates_create_table_statements(): void
    {
        // CREATE TABLE `tags` ...
        $sql1 = 'CREATE TABLE `tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `color_code` varchar(7) NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`)
) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;';

        $parser1 = new Parser($sql1);
        $writer1 = new MigrationDefinitionWriter;
        $writer1->handleCreateTableStatement($parser1->statements[0]);
        $generated1 = $writer1->getUpDefinition();

        $this->assertStringContainsString("Schema::create('tags', function (Blueprint \$table) {", $generated1);
        $this->assertStringContainsString("\$table->bigInteger('id')", $generated1);
        $this->assertStringContainsString('->unsigned()', $generated1);
        $this->assertStringContainsString('->autoIncrement();', $generated1);
        $this->assertStringContainsString("\$table->string('name', 255);", $generated1);
        $this->assertStringContainsString("\$table->string('color_code', 7)", $generated1);
        $this->assertStringContainsString('->nullable();', $generated1);
        $this->assertStringContainsString("\$table->timestamp('created_at')", $generated1);
        $this->assertStringContainsString("\$table->timestamp('updated_at')", $generated1);

        // CREATE TABLE `patient_tag` ...
        $sql2 = 'CREATE TABLE `patient_tag` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `tag_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `patient_patient_id_tag_id_unique` (`patient_id`, `tag_id`),
  INDEX `patient_tag_patient_id_foreign` (`patient_id`),
  INDEX `patient_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `patient_tag_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT `patient_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;';

        $parser2 = new Parser($sql2);
        $writer2 = new MigrationDefinitionWriter;
        $writer2->handleCreateTableStatement($parser2->statements[0]);
        $generated2 = $writer2->getUpDefinition();

        $this->assertStringContainsString("Schema::create('patient_tag', function (Blueprint \$table) {", $generated2);
        $this->assertStringContainsString("\$table->bigInteger('id')", $generated2);
        $this->assertStringContainsString("\$table->bigInteger('patient_id')", $generated2);
        $this->assertStringContainsString("\$table->bigInteger('tag_id')", $generated2);
        $this->assertStringContainsString("\$table->unique(['patient_id', 'tag_id'], 'patient_patient_id_tag_id_unique');", $generated2);
        $this->assertStringContainsString("\$table->index('patient_id', 'patient_tag_patient_id_foreign');", $generated2);
        $this->assertStringContainsString("\$table->index('tag_id', 'patient_tag_tag_id_foreign');", $generated2);
        $this->assertStringContainsString("\$table->foreign('patient_id', 'patient_tag_patient_id_foreign')", $generated2);
        $this->assertStringContainsString("->references('id')", $generated2);
        $this->assertStringContainsString("->on('patients')->onDelete('no action')->onUpdate('no action');", $generated2);
        $this->assertStringContainsString("\$table->foreign('tag_id', 'patient_tag_tag_id_foreign')", $generated2);
        $this->assertStringContainsString("->on('tags')->onDelete('no action')->onUpdate('no action');", $generated2);
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
