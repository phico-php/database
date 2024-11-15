# Database

The Database package provides _multiple connections_ to SQL databases, _fluent schema generation_ and support for _migrations_.

Currently MySQL (and MariaDB), PostgreSQL and SQlite databases are supported through the PDO drivers.

## Features

- **Supports multiple connections** Easily switch connections with the `use()` method
- **Lazy load connections** Connections are not made until they are used
- **Supports prepared statements** Prevent against SQL injection attacks with PDO prepared statements
- **Transactions and Savepoints** Ensure consistency with transactions and savepoints to rollback to
- **Schema generation** Create and update database tables via a fluent API
- **Migrations support** Version your table changes in migrations via the CLI
- **Seeding support** Populate your tables with seed data via the CLI
- **Consistent error handling** All exceptions are returned as DatabaseException instances

## Requirements

The following composer packages are required:

- **phico/cli** For command line support
- **phico/config** For config file support
- **phico/filesystem** For migrations and seed files support

## Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Database](#database)
  - [Connections](#connections)
  - [Queries](#queries)
  - [Transactions](#transactions)
  - [Savepoints](#savepoints)
  - [Exceptions](#exceptions)
- [Migrations](#migrations)
- [Seeds](#seeds)
- [Contributing](#contributing)
- [License](#license)

## Installation

Using composer

```sh
composer require phico/database
```

## Configuration

The database class requires the following config structure.

A single `use` string to identify the default connection followed by a `connections` array detailing your application database connections and settings for `migrations` and `seeds`.

```php
<?php
// this file MUST be named 'database.php' in your config folder

return [

    // define the default connection to use
    'use' => 'test',

    // define the available database connections
    'connections' => [

        // define a connection named 'local'
        'local' => [
            // the driver (mysql, pgsql or sqlite)
            'driver' => 'mysql',
            // the database name (or path for sqlite)
            'database' => 'my-database',
            // the database server name or ip address
            'host' => 'localhost',
            // the database server port (will use default if not specified)
            'port' => 3306,
            // the local unix socket if not connecting via tcp
            'socket' => null,
            // the character set to connect with (ignored by sqlite)
            'charset' => 'utf8_mb4',
            // the user to connect as
            'username' => 'user',
            // the users password
            'password' => 'secret',
            // an array of PDO flags to pass to the connection
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ],

        // define a connection named 'test', note sqlite does not require as much configuration
        'test' => [
            'driver' => 'sqlite',
            'database' => 'storage/database/test.sqlite',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ],

        // optionally define additional connections...

    ],

    // configure migrations support
    'migrations' => [
        // the name of the migrations table
        'table' => '_migrations',
        // the relative path to the migration files (from your application root)
        'path' => 'resources/database/migrations',
        // the connection to use when altering the schema
        'connection' => 'local',
    ],

    // configure seeds support
    'seeds' => [
        // the relative path to the seed files (from your application root)
        'path' => 'resources/database/seeds',
        // the connection to use when seeding the database
        'connection' => 'local',
    ],

];
```

## Usage

This is a quick overview, for more detail please refer to the [Phico database documentation](https://phico-php.net/docs/database).

### DB

The database class is named DB, no Dependency Injection Container configuration is required as it has no constructor arguments.

Instead the configuration is loaded via the `config()` helper which expects to find the config under the `database` key.

**Ensure your config file is named** `database.php`.

```php
// create a new database instance using the default connection
$db = new \Phico\Database\DB;
```

#### Using the helper

Database has a helper method `db()` which returns a new Database instance using the default connection.

```php
// returns a new database instance using the default connection
$db = db();
```

#### Connections

Database supports multiple connections and allows switching between them easily. Multiple connections must be specified in your database config file (see the [Configuration](#configuration) section above for details).

##### Using a different connection

Pass the connection name to the `db()` helper, for example `db('test')` to connect using the _test_ connection.

```php
// returns a new database instance using the connection named `test`
$test = db('test');
```

##### Switching connections

Switch connections using the `use()` method passing the name of a configured connection.

```php
// returns a new database instance using the default connection
$db = db();
// switches to the connection named `test`
$db->use('test');
// get the connection in use
$name = $db->using();
// returns 'test'
```

##### Ad hoc connections

Ad hoc connections can be made by passing the DSN and user credentials to the `connect()` method.

```php
// create an adhoc connection to an in memory sqlite database (no credentials required)
$db = db()->connect('sqlite::memory:');

// create an adhoc connection to a local sqlite database (no credentials required)
$db = db()->connect('sqlite:dbname=storage/database/temp.sqlite');

// create an adhoc connection to a remote postgresql server
$db = db()->connect('pgsql:host=db1.example.com;dbname=countries', 'username', 'password');

// pass any PDO options as the last argument
$db = db()->connect('sqlite:dbname=storage/database/temp.sqlite', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
```

##### Connection attributes

The database attributes for the current connection can be accessed through the `attrs()` method which will return all PDO attributes for the connection in use.

```php
var_dump(db()->attrs());
```

To query a single attribute, for example 'driver name', use the `attr()` method with one of the [PDO::ATTR\_\* constants](https://www.php.net/manual/en/pdo.constants.php#pdo.constants.attr-prefetch).

```php
$driver = db()->attr(PDO_ATTR_DRIVER_NAME); // returns a string 'mysql', 'pgsql' or 'sqlite'
```

#### Queries

Two methods are provided to perform queries on the database, `raw()` and `execute()`.

##### Raw queries

Raw queries **must not** be used with untrusted input as none of the parameters are escaped, it is intended for use with DDL statements to modify the database schema, for general use always use the `execute` method.

```php
// use the admin connection to drop a table
db('admin')->raw('DROP TABLE `example`');
```

##### Execute

The execute method is used to perform general querying and data modification, pass the SQL statement as the first argument and an optional array of parameters as the second.

The parameters argument can be an associative array, or an indexed array

```php
// using assoc array of parameters
db()->execute('DELETE FROM `users` WHERE `users`.`id` = :id', [
    'id' => 456
]);

// using an indexed array of parameters
db()->execute('DELETE FROM `users` WHERE `users`.`id` = ?', [
    456
]);
```

The execute method returns a [PDOStatement](https://www.php.net/manual/en/class.pdostatement.php) instance.

```php
// select rows from the database
$stmt = db('SELECT `name`,`email` FROM `users` WHERE `created_at` >= ? AND `deleted_at` IS NULL', [
    '2024-01-01'
]);

// dump each row in the resultset
foreach ($stmt->fetchAll() as $row) {
    var_dump($row);
}
```

#### Transactions

To ensure data consistency use the DB transaction methods `begin()`, `commit()` and `rollback()` to revert changes if needed.

```php
try {

    $db->begin();
    $db->execute('UPDATE `users` SET `is_active` = 0 WHERE `user_id` IN (?,?,?,?)', $data);
    $db->commit();

} catch (DatabaseException $e) {

    $db->rollback();
    throw $e;

}
```

#### Savepoints

Savepoints allow reverting to a specific point inside a transaction.

```php
try {

    $db->begin();
    $db->execute('UPDATE `users` SET `is_active` = 0 WHERE `user_id` IN (?,?,?,?)', $data);

    // give the savepoint a descriptive name
    $db->savepoint('deactivated-users');

    $db->execute('DELETE FROM `history` WHERE `user_id` IN (?,?,?,?)', $data);

    // multiple savepoints can be saved during a transaction
    $db->savepoint('deleted-user-history');

    $db->execute('DELETE FROM `preferences` WHERE `user_id` IN (?,?,?,?)', $data);
    $db->commit();

} catch (DatabaseException $e) {

    // in this example just deactivating the users is acceptable

    // rollback to the savepoint
    if (!$db->rollbackTo('deactivated-users')) {
        // if the savepoint rollback failed, then revert the whole transaction
        $db->rollback();
    }
    // commit the progress
    $db->commit();

}
```

#### Exceptions

All exceptions are converted to instances of `DatabaseException` to ease separation of database error handling.

```php
try {

    db('DELETE FROM `users` WHERE `deleted_at` <= ?', [
        '2024-01-01'
    ]);

} catch (DatabaseException $e) {
    var_dump($e->toArray());
}
```

### Migrations

This is a quick overview, for more detail please refer to the [Phico migrations documentation](https://phico-php.net/docs/database/migrations).

#### Creating migrations

Use the `create` command to scaffold a new migration file in your configured migrations folder.

```
phico database migrations create insert-name-here
```

Find the new file in your migrations folder and edit the `up()` and `down()` methods. See [table schema](schema.md) for more details.

#### Running migrations

Use the `do` command to run the migration.

```
phico database migrations do
```

#### Listing completed migrations

Use the `done` command to check the migration ran.

```
phico database migrations done
```

#### Reverting migrations

Use the `undo` command to revert the last batch of migrations.

```
phico database migrations undo
```

### Seeds

This is a quick overview, for more detail please refer to the [Phico migrations documentation](https://phico-php.net/docs/database/seeds).

#### Creating seeds

Use the `create` command to scaffold a new seeds file in your configured seeds folder.

```sh
phico database seeds create insert-name-here
```

Find the new file in your `seeds` folder and edit the `seed()` method.

#### Listing seeds

Use the 'list' commands to list available seeds.

```sh
phico database seeds list
```

#### Running seeds

Use the `run` command with the filename of a seed to run the single seed file.

```sh
phico database seeds run 789654321-insert-name-here.php
```

Use the `run all` commands to run all the seeds in the folder.

```sh
phico database seeds run all
```

Note that seeds can be run multiple times, if you do not wish seeds to create duplicate data you will need to ensure that the seed does not run if called more than once.

If you want to easily identify seeded data it may be beneficial to add an `is_seeded` flag to your tables.

<!-- ### Creating tables

Use the `create()` method to create a table

```php
table()->create('table_name');

// use ifNotExists() if needed
table()->create('table_name')->ifNotExists();
````

### Altering tables

Use the `alter()` method to change a table

```php
table()->alter('table_name');
```

### Indicies -->

## Contributing

Database, Migrations and Seeds are considered feature complete, Schema improvements are welcomed.

If you discover any bugs or other issues please create a github issue. If you are able, a pull request with a fix would be helpful.

Please make sure to update any tests and increase coverage as appropriate.

For major changes, please open an issue first to discuss what you would like to change.

## License

[BSD-3-Clause](https://choosealicense.com/licenses/bsd-3-clause/)
