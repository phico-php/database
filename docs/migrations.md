# Migrations

Migration files describe your database tables structure including the indexes, columns and types of data stored in the tables.

Using migrations allows your apps database structure to change over time ensuring that any changes are always defined and repeatable.

Migrations uses a terminal command to scaffold migration files which you will edit to create or amend the tables for your app, then the migrations can be run to make the changes to your database, or rolled back to undo those changes.

## Overview

The `migrations create` terminal command will scaffold an empty migration file in your configured migrations path.

The scaffolded file contains a class with two methods `up()` and `down()` and a database connection instance.

_If you have multiple connections you can switch connections using the DB `use()` method._

The `up()` method should make changes to the database and the `down()` method should roll those changes back.

Running and rolling back migrations is described in detail below.

### TL;DR

Create a migration

```sh
phico database migrations create insert-name-here
```

Find the new file in your migrations folder and edit the `up()` and `down()` methods. See [table schema](schema.md) for more details.

Run the migration

```sh
phico database migrations do
```

Check the migration ran

```sh
phico database migrations done
```

Undo the migration if needed

```sh
phico database migrations undo
```

## Config

The migrations config is held in the `database.php` config file, the defaults are set via the `.env` file or as the second parameter in the `env()` helper call.

The following keys are required:

- **table** - The name of the migrations table in your database, `_migrations` is the default.
- **path** - The relative path to the migrations folder in your app (from your app root).
- **connection** - The preferred database connection to use.

```php
// excerpt from config/database.php
[
    // ... additional config

    'migrations' => [
        'table' => env('DATABASE_MIGRATIONS_TABLE', '_migrations'),
        'path' => env('DATABASE_MIGRATIONS_PATH', 'resources/database/migrations'),
        'connection' => env('DATABASE_MIGRATIONS_CONNECTION', env('DATABASE_USE', 'default')),
    ],

    // ... additional config

];
```

## Terminal commands

### Create

Creates an empty migration file for you to customise.

```sh
phico database migrations create create-users
```

This example will create a new migrations file named `789654321-create-users.php` in your migrations folder where the prefix is the current timestamp.

### Todo

Lists migrations that have not been run

```sh
phico database migrations todo
```

### Done

Lists completed migrations.

```sh
phico database migrations done
```

### Do

Call the `do` command to run all outstanding migrations.

```sh
phico database migrations do
```

Internally migrations are combined together in a _sequence_, they can also be undone together.

### Undo

Use the `undo` command to rollback the last batch of migrations.

```sh
phico database migrations undo
```

Keep calling `undo` to revert batches of migrations until the database is empty.
