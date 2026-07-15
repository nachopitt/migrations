<?php

namespace Nachopitt\Migrations\Tests;

use Illuminate\Support\Facades\File;
use Nachopitt\Migrations\MigrationServiceProvider;
use Orchestra\Testbench\TestCase;

class MigrateImportCommandHandlerTest extends TestCase
{
    protected string $testFixture;
    protected string $migrationPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testFixture = __DIR__ . '/fixtures/test.sql';
        $this->migrationPath = $this->app->databasePath('migrations');

        // Create fixture and migration directories
        if (! is_dir(__DIR__ . '/fixtures')) {
            mkdir(__DIR__ . '/fixtures', 0755, true);
        }
        if (! is_dir($this->migrationPath)) {
            mkdir($this->migrationPath, 0755, true);
        }

        // Create test SQL file
        File::put(
            $this->testFixture,
            <<<'SQL'
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
SQL
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            MigrationServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        // Don't run migrations for this test
    }

    public function test_migrate_import_command_displays_help()
    {
        $this->artisan('migrate:import', ['--help'])
            ->assertExitCode(0);
    }

    public function test_migrate_import_command_with_fixture_generates_migrations()
    {
        $this->artisan('migrate:import', ['file' => $this->testFixture])
            ->assertExitCode(0);

        // Verify migration files were created
        $files = File::glob($this->migrationPath . '/*_create_*.php');
        $this->assertGreaterThan(0, count($files));
    }

    public function test_migrate_import_command_with_table_filter()
    {
        $this->artisan('migrate:import', [
            'file' => $this->testFixture,
            '--table' => 'users',
        ])
            ->assertExitCode(0);

        // Check that only users migration was created
        $files = File::glob($this->migrationPath . '/*_create_*.php');
        $this->assertGreaterThan(0, count($files));
    }

    public function test_migrate_import_command_with_custom_path()
    {
        $customPath = $this->app->basePath('custom_migrations');
        if (! is_dir($customPath)) {
            mkdir($customPath, 0755, true);
        }

        $this->artisan('migrate:import', [
            'file' => $this->testFixture,
            '--path' => $customPath,
        ])
            ->assertExitCode(0);

        File::deleteDirectory($customPath);
    }

    public function test_migrate_import_command_with_nonexistent_file()
    {
        $this->artisan('migrate:import', ['file' => '/nonexistent/file.sql'])
            ->assertExitCode(1);
    }

    public function test_migrate_import_command_with_withoutForeignKeyConstraints()
    {
        $this->artisan('migrate:import', [
            'file' => $this->testFixture,
            '--withoutForeignKeyConstraints' => true,
        ])
            ->assertExitCode(0);
    }

    public function test_migrate_import_command_with_schema_option()
    {
        $this->artisan('migrate:import', [
            'file' => $this->testFixture,
            '--schema' => 'mysql',
        ])
            ->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFixture)) {
            unlink($this->testFixture);
        }

        $fixtureDir = __DIR__ . '/fixtures';
        if (is_dir($fixtureDir)) {
            rmdir($fixtureDir);
        }

        if (is_dir($this->migrationPath)) {
            File::cleanDirectory($this->migrationPath);
            rmdir($this->migrationPath);
        }

        parent::tearDown();
    }
}
