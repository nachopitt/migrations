<?php

namespace Nachopitt\Migrations\Tests;

use Nachopitt\Migrations\Writers\MigrationDefinitionWriter;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PHPUnit\Framework\TestCase;

class GenerateMigrationTest extends TestCase
{
    private static string $generatedMigration;
    private static string $singleTableMigration;

    public static function setUpBeforeClass(): void
    {
        self::$generatedMigration = self::buildGeneratedMigration();
        self::$singleTableMigration = self::buildGeneratedMigration('projects');
    }

    public function test_generates_migration_file_structure()
    {
        $this->assertStringContainsString("<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;", self::$generatedMigration);
        $this->assertStringContainsString('return new class extends Migration', self::$generatedMigration);
        $this->assertStringContainsString('public function up()', self::$generatedMigration);
        $this->assertStringContainsString('public function down()', self::$generatedMigration);
    }

    public function test_generates_expected_create_table_order_and_count()
    {
        $expectedCreateStatements = [
            "Schema::create('project_tag', function (Blueprint \$table) {",
            "Schema::create('project_updates', function (Blueprint \$table) {",
            "Schema::create('projects', function (Blueprint \$table) {",
            "Schema::create('tags', function (Blueprint \$table) {",
            "Schema::create('user_profiles', function (Blueprint \$table) {",
            "Schema::create('user_roles', function (Blueprint \$table) {",
        ];

        $this->assertSame(6, substr_count(self::$generatedMigration, "Schema::create('"));

        $lastPosition = -1;
        foreach ($expectedCreateStatements as $createStatement) {
            $position = strpos(self::$generatedMigration, $createStatement);
            $this->assertNotFalse($position, "Missing create statement: {$createStatement}");
            $this->assertGreaterThan($lastPosition, $position, "Create statement out of order: {$createStatement}");
            $lastPosition = $position;
        }
    }

    public function test_wraps_up_and_down_in_without_foreign_key_constraints()
    {
        $this->assertSame(2, substr_count(self::$generatedMigration, 'Schema::withoutForeignKeyConstraints(function () {'));
    }

    public function test_does_not_emit_redundant_primary_for_auto_increment_columns()
    {
        $this->assertStringContainsString("\$table->bigInteger('id')", self::$generatedMigration);
        $this->assertStringContainsString('->autoIncrement();', self::$generatedMigration);
        $this->assertStringNotContainsString("\$table->primary('id', null);", self::$generatedMigration);
    }

    public function test_generates_project_tag_table_definition()
    {
        $table = $this->extractCreateTableBlock('project_tag');

        $this->assertContainsAll($table, [
            "\$table->bigInteger('id')",
            '->unsigned()',
            '->autoIncrement();',
            "\$table->bigInteger('project_id')",
            "\$table->bigInteger('tag_id')",
            "\$table->timestamp('created_at')",
            "\$table->timestamp('updated_at')",
            "\$table->index('project_id', 'fk_project_tag_projects1_idx');",
            "\$table->index('tag_id', 'fk_project_tag_tags1_idx');",
            "\$table->foreign('project_id', 'fk_project_tag_projects1')",
            "\$table->foreign('tag_id', 'fk_project_tag_tags1')",
            "->references('id')",
            "->on('projects')->onDelete('no action')->onUpdate('no action');",
            "->on('tags')->onDelete('no action')->onUpdate('no action');",
        ]);
    }

    public function test_generates_project_updates_table_definition()
    {
        $table = $this->extractCreateTableBlock('project_updates');

        $this->assertContainsAll($table, [
            "\$table->bigInteger('id')",
            "\$table->text('description');",
            "\$table->string('status', 20);",
            "\$table->tinyInteger('progress_percentage')",
            "\$table->bigInteger('project_id')",
            "\$table->bigInteger('updater_user_id')",
            "\$table->timestamp('created_at')",
            "\$table->timestamp('updated_at')",
            "\$table->timestamp('deleted_at')",
            "\$table->index('project_id', 'fk_project_updates_projects1_idx');",
            "\$table->index('updater_user_id', 'fk_project_updates_users1_idx');",
            "if (Schema::getConnection()->getDriverName() !== 'sqlite') {",
            "\$table->fullText('description', 'project_updates_description_FULLTEXT');",
            "\$table->foreign('project_id', 'fk_project_updates_projects1')",
            "\$table->foreign('updater_user_id', 'fk_project_updates_users1')",
            "->on('projects')->onDelete('no action')->onUpdate('no action');",
            "->on('users')->onDelete('no action')->onUpdate('no action');",
        ]);
    }

    public function test_generates_projects_table_definition()
    {
        $table = $this->extractCreateTableBlock('projects');

        $this->assertContainsAll($table, [
            "\$table->bigInteger('id')",
            "\$table->string('title', 255);",
            "\$table->text('description')",
            "\$table->string('priority', 20);",
            "\$table->string('current_status', 20);",
            "\$table->tinyInteger('current_progress_percentage')",
            "\$table->date('start_date')",
            "\$table->date('due_date')",
            "\$table->date('end_date')",
            "\$table->bigInteger('parent_id')",
            "\$table->bigInteger('reporter_user_id')",
            "\$table->bigInteger('assigned_user_id')",
            "\$table->timestamp('created_at')",
            "\$table->timestamp('updated_at')",
            "\$table->timestamp('deleted_at')",
            "\$table->index('parent_id', 'fk_projects_projects1_idx');",
            "\$table->index('reporter_user_id', 'fk_projects_users1_idx');",
            "\$table->index('assigned_user_id', 'fk_projects_users2_idx');",
            "if (Schema::getConnection()->getDriverName() !== 'sqlite') {",
            "\$table->fullText('title', 'projects_title_FULLTEXT');",
            "\$table->fullText('description', 'projects_description_FULLTEXT');",
            "\$table->foreign('parent_id', 'fk_projects_projects1')",
            "\$table->foreign('reporter_user_id', 'fk_projects_users1')",
            "\$table->foreign('assigned_user_id', 'fk_projects_users2')",
            "->on('projects')->onDelete('no action')->onUpdate('no action');",
            "->on('users')->onDelete('no action')->onUpdate('no action');",
        ]);
    }

    public function test_generates_tags_table_definition()
    {
        $table = $this->extractCreateTableBlock('tags');

        $this->assertContainsAll($table, [
            "\$table->bigInteger('id')",
            "\$table->string('name', 255);",
            "\$table->timestamp('created_at')",
            "\$table->timestamp('updated_at')",
            "\$table->timestamp('deleted_at')",
            "\$table->unique('name', 'name_UNIQUE');",
        ]);
    }

    public function test_generates_user_profiles_table_definition()
    {
        $table = $this->extractCreateTableBlock('user_profiles');

        $this->assertContainsAll($table, [
            "\$table->bigInteger('id')",
            "\$table->string('first_name', 255);",
            "\$table->string('last_name', 255);",
            "\$table->tinyInteger('active')",
            "\$table->bigInteger('user_id')",
            "\$table->timestamp('created_at')",
            "\$table->timestamp('updated_at')",
            "\$table->index('user_id', 'fk_user_profiles_users1_idx');",
            "\$table->unique('user_id', 'user_id_UNIQUE');",
            "\$table->foreign('user_id', 'fk_user_profiles_users1')",
            "->on('users')->onDelete('no action')->onUpdate('no action');",
        ]);
    }

    public function test_generates_user_roles_table_definition()
    {
        $table = $this->extractCreateTableBlock('user_roles');

        $this->assertContainsAll($table, [
            "\$table->bigInteger('id')",
            "\$table->string('role', 20);",
            "\$table->bigInteger('user_id')",
            "\$table->timestamp('created_at')",
            "\$table->timestamp('updated_at')",
            "\$table->index('user_id', 'fk_user_roles_users1_idx');",
            "\$table->foreign('user_id', 'fk_user_roles_users1')",
            "->on('users')->onDelete('no action')->onUpdate('no action');",
        ]);
    }

    public function test_generates_down_method_with_expected_drop_order()
    {
        $expectedDrops = [
            "Schema::dropIfExists('project_tag');",
            "Schema::dropIfExists('project_updates');",
            "Schema::dropIfExists('projects');",
            "Schema::dropIfExists('tags');",
            "Schema::dropIfExists('user_profiles');",
            "Schema::dropIfExists('user_roles');",
        ];

        $lastPosition = -1;

        foreach ($expectedDrops as $dropStatement) {
            $position = strpos(self::$generatedMigration, $dropStatement);
            $this->assertNotFalse($position, "Missing drop statement: {$dropStatement}");
            $this->assertGreaterThan($lastPosition, $position, "Drop statement out of order: {$dropStatement}");
            $lastPosition = $position;
        }
    }

        public function test_single_table_generation_includes_only_selected_create_statement()
        {
            $this->assertSame(1, substr_count(self::$singleTableMigration, "Schema::create('"));
            $this->assertStringContainsString("Schema::create('projects', function (Blueprint \$table) {", self::$singleTableMigration);
            $this->assertStringNotContainsString("Schema::create('project_tag', function (Blueprint \$table) {", self::$singleTableMigration);
            $this->assertStringNotContainsString("Schema::create('project_updates', function (Blueprint \$table) {", self::$singleTableMigration);
            $this->assertStringNotContainsString("Schema::create('tags', function (Blueprint \$table) {", self::$singleTableMigration);
            $this->assertStringNotContainsString("Schema::create('user_profiles', function (Blueprint \$table) {", self::$singleTableMigration);
            $this->assertStringNotContainsString("Schema::create('user_roles', function (Blueprint \$table) {", self::$singleTableMigration);
        }

        public function test_single_table_generation_has_only_selected_drop_statement()
        {
            $this->assertSame(1, substr_count(self::$singleTableMigration, "Schema::dropIfExists('"));
            $this->assertStringContainsString("Schema::dropIfExists('projects');", self::$singleTableMigration);
            $this->assertStringNotContainsString("Schema::dropIfExists('project_tag');", self::$singleTableMigration);
            $this->assertStringNotContainsString("Schema::dropIfExists('project_updates');", self::$singleTableMigration);
            $this->assertStringNotContainsString("Schema::dropIfExists('tags');", self::$singleTableMigration);
            $this->assertStringNotContainsString("Schema::dropIfExists('user_profiles');", self::$singleTableMigration);
            $this->assertStringNotContainsString("Schema::dropIfExists('user_roles');", self::$singleTableMigration);
        }

      private function extractCreateTableBlock(string $tableName): string
      {
            $startNeedle = "Schema::create('{$tableName}', function (Blueprint \$table) {";
            $startPosition = strpos(self::$generatedMigration, $startNeedle);

            $this->assertNotFalse($startPosition, "Missing create block start for {$tableName}");

            $nextCreatePosition = strpos(self::$generatedMigration, "\n            Schema::create('", $startPosition + strlen($startNeedle));
            $upMethodEndPosition = strpos(self::$generatedMigration, "\n        });\n    }\n\n    /**", $startPosition + strlen($startNeedle));

            $endCandidates = [];
            if ($nextCreatePosition !== false) {
                $endCandidates[] = $nextCreatePosition;
            }

            if ($upMethodEndPosition !== false) {
                $endCandidates[] = $upMethodEndPosition;
            }

            $this->assertNotEmpty($endCandidates, "Missing create block end for {$tableName}");

            return substr(self::$generatedMigration, $startPosition, min($endCandidates) - $startPosition);
      }

      private function assertContainsAll(string $haystack, array $needles): void
      {
            foreach ($needles as $needle) {
                $this->assertStringContainsString($needle, $haystack);
            }
      }

    private static function buildGeneratedMigration(?string $onlyTable = null): string
    {
        $sql = <<<'SQL'
-- MySQL Script generated by MySQL Workbench
-- Fri Sep 19 08:46:51 2025
-- Model: New Model    Version: 1.0
-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema proyex
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Table `project_tag`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_tag` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `tag_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_project_tag_projects1_idx` (`project_id` ASC) VISIBLE,
  INDEX `fk_project_tag_tags1_idx` (`tag_id` ASC) VISIBLE,
  CONSTRAINT `fk_project_tag_projects1`
    FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_project_tag_tags1`
    FOREIGN KEY (`tag_id`)
    REFERENCES `tags` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `project_updates`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_updates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `description` TEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL,
  `progress_percentage` TINYINT UNSIGNED NOT NULL,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `updater_user_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_project_updates_projects1_idx` (`project_id` ASC) VISIBLE,
  INDEX `fk_project_updates_users1_idx` (`updater_user_id` ASC) VISIBLE,
  FULLTEXT INDEX `project_updates_description_FULLTEXT` (`description`) VISIBLE,
  CONSTRAINT `fk_project_updates_projects1`
    FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_project_updates_users1`
    FOREIGN KEY (`updater_user_id`)
    REFERENCES `users` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `projects`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `projects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `priority` VARCHAR(20) NOT NULL,
  `current_status` VARCHAR(20) NOT NULL,
  `current_progress_percentage` TINYINT UNSIGNED NOT NULL,
  `start_date` DATE NULL,
  `due_date` DATE NULL,
  `end_date` DATE NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `reporter_user_id` BIGINT UNSIGNED NOT NULL,
  `assigned_user_id` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_projects_projects1_idx` (`parent_id` ASC) VISIBLE,
  INDEX `fk_projects_users1_idx` (`reporter_user_id` ASC) VISIBLE,
  INDEX `fk_projects_users2_idx` (`assigned_user_id` ASC) VISIBLE,
  FULLTEXT INDEX `projects_title_FULLTEXT` (`title`) VISIBLE,
  FULLTEXT INDEX `projects_description_FULLTEXT` (`description`) VISIBLE,
  CONSTRAINT `fk_projects_projects1`
    FOREIGN KEY (`parent_id`)
    REFERENCES `projects` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_projects_users1`
    FOREIGN KEY (`reporter_user_id`)
    REFERENCES `users` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_projects_users2`
    FOREIGN KEY (`assigned_user_id`)
    REFERENCES `users` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `tags`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tags` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC) VISIBLE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `user_profiles`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_profiles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(255) NOT NULL,
  `last_name` VARCHAR(255) NOT NULL,
  `active` TINYINT(1) UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_user_profiles_users1_idx` (`user_id` ASC) VISIBLE,
  UNIQUE INDEX `user_id_UNIQUE` (`user_id` ASC) VISIBLE,
  CONSTRAINT `fk_user_profiles_users1`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `user_roles`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(20) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_user_roles_users1_idx` (`user_id` ASC) VISIBLE,
  CONSTRAINT `fk_user_roles_users1`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SQL;

        $parser = new Parser($sql);
        $migrationWriter = new MigrationDefinitionWriter();

        // mimic squash + withoutForeignKeyConstraints behavior
        $migrationWriter->reset();
        $migrationWriter->beginWithoutForeignKeyConstraints();

        foreach ($parser->statements as $statement) {
            if ($statement instanceof CreateStatement && in_array('TABLE', $statement->options->options)) {
                if ($onlyTable !== null && $statement->name->table !== $onlyTable) {
                    continue;
                }

                $migrationWriter->handleCreateTableStatement($statement);
            }
        }

        $migrationWriter->endWithoutForeignKeyConstraints();

        $up = $migrationWriter->getUpDefinition();
        $down = $migrationWriter->getDownDefinition();

        $stub = file_get_contents(__DIR__ . '/../src/stubs/migration.create.stub');

        $generated = str_replace(['DummyUpDefinition', '{{ upDefinition }}', '{{upDefinition}}'], $up, $stub);
        $generated = str_replace(['DummyDownDefinition', '{{ downDefinition }}', '{{downDefinition}}'], $down, $generated);

        return rtrim(str_replace(["\r\n", "\r"], "\n", $generated));
    }
}
