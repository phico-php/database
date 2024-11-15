# Schema

Schema is a fluent API for creating and modifying database tables, the main call is the `table()` function which returns an instance of the Table class. Once the table modifications are complete, call the `toSql()` method or cast the table instance to a string to get the SQL statement to be executed.

Currently MySQL, SQLite and PostgreSQL dialects are supported.

## Tables

### Creating tables

First create a table instance using the `table()` helper.
<br>This will use the _default_ database connection as defined in your config.

```php
$table = table();

// pass a specific connection in via the table() function if needed.
$table = table($db->use('admin'));
```

Use the `create()` method to create a new table.

```php
$table->create('users');
```

Then define the table columns.

```php
$table->integer('id')->primary()->autoIncrement();
$table->string('name', 128);
$table->string('email', 128);
$table->string('hash', 64);
$table->datetime('signedin_at')->nullable();
$table->timestamps();
$table->softDelete();
```

Finally call `toSql()` or cast `$table` to a string to get the SQL statement.

```php
$table->toSql();
// or
$sql = (string) $table;
```

A complete migration `up()` method would look like this

```php
public function up(): void
{
    // fetch the admin connection with permission to create tables
    $admin = $this->db->use('admin');

    // pass the admin connection to table
    $table = table($admin);

    // set the table name
    $table->create('users');

    // define the table columns
    $table->integer('id')->primary()->autoIncrement();
    $table->string('name', 128);
    $table->string('email', 128);
    $table->string('hash', 64);
    $table->datetime('signedin_at')->nullable();
    $table->timestamps();
    $table->softDelete();

    // execute the create table statement
    $this->db->raw($table->toSql());
}
```

### Dropping tables

Pass the name of the table to the `drop()` method.

```php
(string) table()->drop('users');
// DROP TABLE users
```

If the table might not exist use the `ifExists()` or `dropIfExists()` methods.

```php
$table = table()->dropIfExists('users');
// or
$table = table()
    ->drop('users')
    ->ifExists();
```

## Columns

Many column types are defined, they will automatically adjust to the best column type for the database in use.

### Adding columns

```php

```

### Renaming columns

### Dropping columns

## Indexes

### Creating indexes

```php
$table->index('email', 'idx_users_email');
```

```php
$table->unique('email');
```

### Dropping indexes

```php
$table->dropIndex('email');
```
