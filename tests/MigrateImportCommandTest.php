<?php

namespace Nachopitt\Migrations\Tests;

use Nachopitt\Migrations\Console\Commands\MigrateImportCommand;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MigrateImportCommandTest extends TestCase
{
    public function test_signature_exposes_supported_options()
    {
        $signature = $this->readProtectedProperty('signature');

        $this->assertStringContainsString('{--schema=', $signature);
        $this->assertStringContainsString('{--table=', $signature);
        $this->assertStringContainsString('{--path=', $signature);
        $this->assertStringContainsString('{--realpath', $signature);
        $this->assertStringContainsString('{--fullpath', $signature);
        $this->assertStringContainsString('{--squash', $signature);
        $this->assertStringContainsString('{--withoutForeignKeyConstraints', $signature);
    }

    public function test_get_statement_table_name_handles_create_alter_and_drop_statements()
    {
        $create = $this->parseStatement('CREATE TABLE `projects` (`id` BIGINT NOT NULL)', CreateStatement::class);
        $alter = $this->parseStatement('ALTER TABLE `projects` ADD COLUMN `title` VARCHAR(255) NULL', AlterStatement::class);
        $drop = $this->parseStatement('DROP TABLE IF EXISTS `projects`', DropStatement::class);

        $this->assertSame('projects', $this->invokeGetStatementTableName($create));
        $this->assertSame('projects', $this->invokeGetStatementTableName($alter));
        $this->assertSame('projects', $this->invokeGetStatementTableName($drop));
    }

    private function readProtectedProperty(string $property): mixed
    {
        $reflection = new ReflectionClass(MigrateImportCommand::class);
        $instance = $reflection->newInstanceWithoutConstructor();
        $propertyReflection = $reflection->getProperty($property);
        $propertyReflection->setAccessible(true);

        return $propertyReflection->getValue($instance);
    }

    private function invokeGetStatementTableName(object $statement): ?string
    {
        $reflection = new ReflectionClass(MigrateImportCommand::class);
        $instance = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('getStatementTableName');
        $method->setAccessible(true);

        return $method->invoke($instance, $statement);
    }

    private function parseStatement(string $sql, string $expectedClass): object
    {
        $parser = new Parser($sql);
        $statement = $parser->statements[0] ?? null;

        $this->assertInstanceOf($expectedClass, $statement);

        return $statement;
    }
}
