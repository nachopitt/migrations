# Migrations

Migrations is a Laravel package that adds a new migrate:* artisan command: import.

You can easily create new migration files by using a SQL import file as an input for the command.

It is based on stock make:migrate artisan command.

## Installation

```bash
composer require nachopitt/migrations
```

## Usage

```bash
php artisan migrate:import {file? : The SQL file to be imported}
    {--schema= : The name of the schema}
    {--path= : The location where the migration file should be created}
    {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
    {--fullpath : Output the full path of the migration}
    {--squash : Generate one migration file instead of multiple files}
```

If `file` argument is not provided, the default value would be `database_model/${database}.sql`, where `${database}` is the default database name.

### Options

*   `--schema`: The name of the schema to be used in the migration.
*   `--path`: The location where the migration file should be created.
*   `--realpath`: Indicate that the provided migration file paths are pre-resolved absolute paths.
*   `--fullpath`: Output the full path of the migration.
*   `--squash`: Generate one migration file instead of multiple files. This is useful when you want to combine multiple SQL statements into a single migration.

Once finished, the new migration files will be placed in the usual migrations directory (`database/migrations`) and will follow the usual naming conventions.

The name of the migration files will depend on the number of `CREATE`, `ALTER` and `DROP` SQL statements.

## Features

The `migrate:import` command is able to handle `CREATE`, `ALTER` and `DROP` SQL statements.

When used in conjuntion with a tool like [PHP MySQL Diff](https://github.com/camcima/php-mysql-diff) the process of generating the migration files could be automated somehow.

## Contributing

Pull requests are welcome. For major changes, please open an issue first
to discuss what you would like to change.

## License

[MIT](https://choosealicense.com/licenses/mit/)
