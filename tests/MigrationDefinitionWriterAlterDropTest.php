<?php

namespace Nachopitt\Migrations\Tests;

use Nachopitt\Migrations\Writers\MigrationDefinitionWriter;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PHPUnit\Framework\TestCase;

class MigrationDefinitionWriterAlterDropTest extends TestCase
{
    public function test_generates_add_column_alter_statements()
    {
        [$up, $down] = $this->buildAlterDefinitions(
            'ALTER TABLE `projects` ADD COLUMN `archived_at` TIMESTAMP NULL AFTER `updated_at`'
        );

        $this->assertContainsAll($up, [
            "Schema::table('projects', function (Blueprint \$table) {",
            "\$table->timestamp('archived_at')",
            "->after('updated_at')",
            '->nullable();',
        ]);

        $this->assertContainsAll($down, [
            "Schema::table('projects', function (Blueprint \$table) {",
            "\$table->dropColumn('archived_at');",
        ]);
    }

    public function test_generates_change_column_alter_statements_and_revert_comment()
    {
        [$up, $down] = $this->buildAlterDefinitions(
            'ALTER TABLE `projects` CHANGE COLUMN `title` `name` VARCHAR(150) NULL'
        );

        $this->assertContainsAll($up, [
            "Schema::table('projects', function (Blueprint \$table) {",
            "\$table->string('title', 150)",
            '->nullable()',
            '->change();',
            "\$table->renameColumn('title', 'name');",
        ]);

        $this->assertStringContainsString(
            '// Revert manually CHANGE COLUMN title alter operation to the previous definition.',
            $down
        );
    }

    public function test_generates_add_index_unique_and_fulltext_alter_statements()
    {
        [$indexUp, $indexDown] = $this->buildAlterDefinitions(
            'ALTER TABLE `projects` ADD INDEX `projects_status_idx` (`current_status`)'
        );
        [$uniqueUp, $uniqueDown] = $this->buildAlterDefinitions(
            'ALTER TABLE `projects` ADD UNIQUE INDEX `projects_title_unique` (`title`)'
        );
        [$fullTextUp, $fullTextDown] = $this->buildAlterDefinitions(
            'ALTER TABLE `projects` ADD FULLTEXT INDEX `projects_description_fulltext` (`description`)'
        );

        $this->assertContainsAll($indexUp, [
            "\$table->index('current_status', 'projects_status_idx');",
        ]);
        $this->assertContainsAll($indexDown, [
            "\$table->dropIndex('projects_status_idx');",
        ]);

        $this->assertContainsAll($uniqueUp, [
            "\$table->unique('title', 'projects_title_unique');",
        ]);
        $this->assertContainsAll($uniqueDown, [
            "\$table->dropUnique('projects_title_unique');",
        ]);

        $this->assertContainsAll($fullTextUp, [
            "if (Schema::getConnection()->getDriverName() !== 'sqlite') {",
            "\$table->fullText('description', 'projects_description_fulltext');",
        ]);
        $this->assertContainsAll($fullTextDown, [
            "if (Schema::getConnection()->getDriverName() !== 'sqlite') {",
            "\$table->dropFullText('projects_description_fulltext');",
        ]);
    }

    public function test_generates_add_foreign_key_constraint_alter_statements()
    {
        [$up, $down] = $this->buildAlterDefinitions(
            'ALTER TABLE `projects` ADD CONSTRAINT `fk_projects_users3` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION'
        );

        $this->assertContainsAll($up, [
            "\$table->foreign('owner_user_id', 'fk_projects_users3')",
            "->references('id')",
            "->on('users')",
            "->onDelete('cascade')",
            "->onUpdate('no action');",
        ]);

        $this->assertContainsAll($down, [
            "\$table->dropForeign('fk_projects_users3');",
        ]);
    }

    public function test_generates_rename_table_column_and_index_alter_statements()
    {
        [$renameTableUp, $renameTableDown] = $this->buildAlterDefinitions(
            'ALTER TABLE `projects` RENAME TO `portfolio_projects`'
        );
        [$renameColumnUp, $renameColumnDown] = $this->buildAlterDefinitions(
            'ALTER TABLE `projects` RENAME COLUMN `title` TO `project_title`'
        );
        [$renameIndexUp, $renameIndexDown] = $this->buildAlterDefinitions(
            'ALTER TABLE `projects` RENAME INDEX `fk_projects_users1_idx` TO `projects_reporter_idx`'
        );

        $this->assertContainsAll($renameTableUp, [
            "\$table->rename('portfolio_projects');",
        ]);
        $this->assertContainsAll($renameTableDown, [
            "\$table->rename('projects');",
        ]);

        $this->assertContainsAll($renameColumnUp, [
            "\$table->renameColumn('title', 'project_title');",
        ]);
        $this->assertContainsAll($renameColumnDown, [
            "\$table->renameColumn('project_title', 'title');",
        ]);

        $this->assertContainsAll($renameIndexUp, [
            "\$table->renameIndex('fk_projects_users1_idx', 'projects_reporter_idx');",
        ]);
        $this->assertContainsAll($renameIndexDown, [
            "\$table->renameIndex('projects_reporter_idx', 'fk_projects_users1_idx');",
        ]);
    }

    public function test_generates_drop_column_index_and_foreign_key_alter_statements()
    {
        [$dropColumnUp, $dropColumnDown] = $this->buildAlterDefinitions(
            'ALTER TABLE `projects` DROP COLUMN `archived_at`'
        );
        [$dropIndexUp, $dropIndexDown] = $this->buildAlterDefinitions(
            'ALTER TABLE `projects` DROP INDEX `projects_status_idx`'
        );
        [$dropForeignUp, $dropForeignDown] = $this->buildAlterDefinitions(
            'ALTER TABLE `projects` DROP FOREIGN KEY `fk_projects_users3`'
        );

        $this->assertContainsAll($dropColumnUp, [
            "\$table->dropColumn('archived_at');",
        ]);
        $this->assertStringContainsString(
            '// Revert manually DROP COLUMN archived_at alter operation to the previous definition.',
            $dropColumnDown
        );

        $this->assertContainsAll($dropIndexUp, [
            "\$table->dropIndex('projects_status_idx');",
        ]);
        $this->assertStringContainsString(
            '// Revert manually DROP INDEX projects_status_idx alter operation to the previous definition.',
            $dropIndexDown
        );

        $this->assertContainsAll($dropForeignUp, [
            "\$table->dropForeign('fk_projects_users3');",
        ]);
        $this->assertStringContainsString(
            '// Revert manually DROP FOREIGN fk_projects_users3 alter operation to the previous definition.',
            $dropForeignDown
        );
    }

    public function test_generates_drop_table_statements()
    {
        $writer = new MigrationDefinitionWriter;
        $statement = $this->parseStatement(
            'DROP TABLE IF EXISTS `project_tag`, `project_updates`',
            DropStatement::class
        );

        $writer->handleDropTableStatement($statement);

        $this->assertContainsAll($writer->getUpDefinition(), [
            "Schema::dropIfExists('project_tag');",
            "Schema::dropIfExists('project_updates');",
        ]);

        $this->assertContainsAll($writer->getDownDefinition(), [
            '// Revert manually DROP TABLE project_tag statement to the previous definition.',
            '// Revert manually DROP TABLE project_updates statement to the previous definition.',
        ]);
    }

    public function test_generates_modify_column_alter_statements_with_comments_and_json()
    {
        // 1. ALTER TABLE `appointment_procedures` MODIFY COLUMN `price_charged` decimal(10,2) NOT NULL;
        [$up1, $down1] = $this->buildAlterDefinitions(
            'ALTER TABLE `appointment_procedures` MODIFY COLUMN `price_charged` decimal(10,2) NOT NULL'
        );
        $this->assertContainsAll($up1, [
            "Schema::table('appointment_procedures', function (Blueprint \$table) {",
            "\$table->decimal('price_charged', 10, 2)",
            '->change();',
        ]);
        $this->assertStringContainsString(
            '// Revert manually CHANGE COLUMN price_charged alter operation to the previous definition.',
            $down1
        );

        // 2. ALTER TABLE `appointments` MODIFY COLUMN `status` varchar(255) NOT NULL COMMENT "`scheduled`, `confirmed`, `arrived`, `in_chair`, `completed`, `cancelled`, `no_show`", MODIFY COLUMN `notes` text NULL COMMENT "Reason for appointment / details";
        [$up2, $down2] = $this->buildAlterDefinitions(
            'ALTER TABLE `appointments` MODIFY COLUMN `status` varchar(255) NOT NULL COMMENT "`scheduled`, `confirmed`, `arrived`, `in_chair`, `completed`, `cancelled`, `no_show`", MODIFY COLUMN `notes` text NULL COMMENT "Reason for appointment / details"'
        );
        $this->assertContainsAll($up2, [
            "Schema::table('appointments', function (Blueprint \$table) {",
            "\$table->string('status', 255)",
            "->comment('`scheduled`, `confirmed`, `arrived`, `in_chair`, `completed`, `cancelled`, `no_show`')",
            '->change();',
            "\$table->text('notes')",
            "->comment('Reason for appointment / details')",
            '->nullable()',
            '->change();',
        ]);
        $this->assertStringContainsString(
            '// Revert manually CHANGE COLUMN status alter operation to the previous definition.',
            $down2
        );
        $this->assertStringContainsString(
            '// Revert manually CHANGE COLUMN notes alter operation to the previous definition.',
            $down2
        );

        // 3. ALTER TABLE `medical_histories` MODIFY COLUMN `allergies` text NULL COMMENT "Penicillin, Latex", MODIFY COLUMN `medications` text NULL COMMENT "Aspirin", MODIFY COLUMN `notes` text NULL COMMENT "Clinical medical notes", ADD COLUMN `preexisting_conditions` json NULL COMMENT "{\"diabetes\": true, \"hypertension\": false}" AFTER `medications`;
        [$up3, $down3] = $this->buildAlterDefinitions(
            'ALTER TABLE `medical_histories` MODIFY COLUMN `allergies` text NULL COMMENT "Penicillin, Latex", MODIFY COLUMN `medications` text NULL COMMENT "Aspirin", MODIFY COLUMN `notes` text NULL COMMENT "Clinical medical notes", ADD COLUMN `preexisting_conditions` json NULL COMMENT "{\"diabetes\": true, \"hypertension\": false}" AFTER `medications`'
        );
        $this->assertContainsAll($up3, [
            "Schema::table('medical_histories', function (Blueprint \$table) {",
            "\$table->text('allergies')",
            "->comment('Penicillin, Latex')",
            '->nullable()',
            '->change();',
            "\$table->text('medications')",
            "->comment('Aspirin')",
            '->nullable()',
            '->change();',
            "\$table->text('notes')",
            "->comment('Clinical medical notes')",
            '->nullable()',
            '->change();',
            "\$table->json('preexisting_conditions')",
            "->comment('{\"diabetes\": true, \"hypertension\": false}')",
            "->after('medications')",
            '->nullable();',
        ]);
        $this->assertStringContainsString(
            '// Revert manually CHANGE COLUMN allergies alter operation to the previous definition.',
            $down3
        );
        $this->assertContainsAll($down3, [
            "\$table->dropColumn('preexisting_conditions');",
        ]);

        // 4. ALTER TABLE `patients` ADD FULLTEXT INDEX `patients_fulltext` (`first_name`, `last_name`, `email`, `phone`, `notes`);
        [$up4, $down4] = $this->buildAlterDefinitions(
            'ALTER TABLE `patients` ADD FULLTEXT INDEX `patients_fulltext` (`first_name`, `last_name`, `email`, `phone`, `notes`)'
        );
        $this->assertContainsAll($up4, [
            "if (Schema::getConnection()->getDriverName() !== 'sqlite') {",
            "\$table->fullText(['first_name', 'last_name', 'email', 'phone', 'notes'], 'patients_fulltext');",
        ]);
        $this->assertContainsAll($down4, [
            "if (Schema::getConnection()->getDriverName() !== 'sqlite') {",
            "\$table->dropFullText('patients_fulltext');",
        ]);
    }

    public function test_generates_add_column_alter_statement_with_default_quote()
    {
        [$up, $down] = $this->buildAlterDefinitions(
            "ALTER TABLE `teams` ADD COLUMN `timezone` VARCHAR(255) NULL DEFAULT 'UTC' AFTER `is_personal`"
        );

        $this->assertContainsAll($up, [
            "Schema::table('teams', function (Blueprint \$table) {",
            "\$table->string('timezone', 255)",
            "->default('UTC')",
            "->after('is_personal')",
            '->nullable();',
        ]);
    }

    private function buildAlterDefinitions(string $sql): array
    {
        $writer = new MigrationDefinitionWriter;
        $statement = $this->parseStatement($sql, AlterStatement::class);

        $writer->handleAlterTableStatement($statement);

        return [$writer->getUpDefinition(), $writer->getDownDefinition()];
    }

    private function parseStatement(string $sql, string $expectedClass)
    {
        $parser = new Parser($sql);
        $statement = $parser->statements[0] ?? null;

        $this->assertInstanceOf($expectedClass, $statement);

        return $statement;
    }

    private function assertContainsAll(string $haystack, array $needles): void
    {
        foreach ($needles as $needle) {
            $this->assertStringContainsString($needle, $haystack);
        }
    }
}
