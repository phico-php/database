# Seeds

Seeds populate your database tables with data, perhaps, test data, country codes, currencies or other queryable but static data.

_It may be a good idea to add a `seeded` column to your tables so you can easily identify the seeded data at a later date._

## Overview

The `seeds create` terminal command will scaffold an empty seed file in your configured seeds path.

The scaffolded file contains a class with a single method `seed()` and a database connection instance.

_If you have multiple connections you can switch connections using the DB `use()` method._

Running seeds is described in detail below.

### TL;DR

Create a seed

```sh
phico database seeds create insert-name-here
```

Find the new file in your `seeds` folder and edit the `seed()` method.

List all the seeds

```sh
phico database seeds list
```

Run a single seed file

```sh
phico database seeds run 789654321-insert-name-here.php
```

Or run all the seeds in the folder

```sh
phico database seeds run all
```

## Config

The seeds config is held in the `database.php` config file, the defaults are set via the `.env` file or as the second parameter in the `env()` helper call.

The following keys are required:

- **path** - The relative path to the seeds folder in your app (from your app root).
- **connection** - The preferred database connection to use.

```php
// excerpt from config/database.php
[
    // ... additional config

    'seeds' => [
        'path' => env('DATABASE_SEEDS_PATH', 'resources/database/seeds'),
        'connection' => env('DATABASE_SEEDS_CONNECTION', env('DATABASE_USE', 'default')),
    ],

    // ... additional config

];
```

## Terminal commands

### Create

Creates an empty seed file for you to customise.

```sh
phico database seeds create populate-admin-users
```

This example will create a new seed file named `789654321-populate-admin-users.php` in your seeds folder where the prefix is the current timestamp.

### List

Lists all files in the seeds folder.

```sh
phico database seeds list
```

### Run

Runs a single named seed, note that you may run the seed multiple times.

```sh
phico database seeds run 789654321-populate-admin-users.php
```

### Run all

Runs all seeds, note that previously run seeds will be run again.

```sh
phico database seeds run all
```
