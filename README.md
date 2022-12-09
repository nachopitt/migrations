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
php artisan migrate:import [file=path/to/import/file.sql]
```

If file argument is not provided, the default value would be "database_model/${schema}_diff.sql"

Once finished, the new migration files will be placed in the usual migrations directory (database/migrations) and will follow the usual naming conventions.

The name of the migration files will depend on the number of CREATE and ALTER SQL statements.

## Current status

So far, the migrate:import command is able to handle only SQL CREATE statements, but the intention is to add support for ALTER statements as well.

That way, when used in conjuntion with a tool like PHP MySQL Diff (https://github.com/camcima/php-mysql-diff) the process of generating the migration files could be automated somehow.

## Contributing

Pull requests are welcome. For major changes, please open an issue first
to discuss what you would like to change.

## License

[MIT](https://choosealicense.com/licenses/mit/)
