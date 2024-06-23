# Schema

Defines and modifies the structure of database tables and indices.

Supports MySQL, MariaDB, PostgreSQL and SQlite dialects.


## Installation

Using composer

```sh
composer require phico/schema
```

## Usage

### Creating tables

Use the `create()` method to create a table

```php
table()->create('table_name');

// use ifNotExists() if needed
table()->create('table_name')->ifNotExists();
```

### Altering tables

Use the `alter()` method to change a table

```php
table()->alter('table_name');
```

### Indicies

## Contributing

Schema is considered feature complete, however if you discover any bugs or issues in it's behaviour or performance please create an issue, if you are able a pull request with a fix would be helpful.

Please make sure to update tests as appropriate.

For major changes, please open an issue first to discuss what you would like to change.

## License

[BSD-3-Clause](https://choosealicense.com/licenses/bsd-3-clause/)
