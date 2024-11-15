<?php

declare(strict_types=1);

use Phico\Database\Connection\Connection;
use Phico\Database\Connection\Factory;

// with this defined the database config will come from ./config/database.php
define('PHICO_PATH_ROOT', __DIR__);


test('can be created with config', function () {

    $f = new Factory([
        'use' => 'default',
        'connections' => [
            'default' => [
                'driver' => 'sqlite',
                'database' => 'memory'
            ]
        ]
    ]);

    expect($f)->toBeInstanceOf(Factory::class);

});
test('can be created without config (fetches from file)', function () {

    $f = new Factory();

    expect($f)->toBeInstanceOf(Factory::class);

});
test('throws exception if config does not have use key', function () {

    $f = new Factory([
        'connections' => []
    ]);

    expect($f)->toBeInstanceOf(Factory::class);

})->throws(\InvalidArgumentException::class, "Cannot get the default database connection, please add the 'use' key to your database config.");

test('throws exception if config has invalid connection', function () {

    $f = new Factory([
        'use' => 'default',
        'connections' => [
            'default' => [
                'foo' => 'bar',
                'bar' => 'foo'
            ]
        ]
    ]);

})->throws(\InvalidArgumentException::class, "Cannot create Config from array as it is missing the driver name");

// can get default connection (without name arg)
test('can get default connection (without name arg)', function () {

    $f = new Factory();

    expect($f->get())->toBeInstanceOf(Connection::class);

});
// can get connection by name
test('can get connection by name', function () {

    $f = new Factory();

    $c = $f->get('memory');

    expect($c)->toBeInstanceOf(Connection::class);
    expect($c->getName())->toBe('memory');

});

// throws exception if named connection does not exist in config
test('throws exception if named connection does not exist in config', function () {

    $f = new Factory([
        'use' => 'default',
        'connections' => [

        ]
    ]);

    $f->get('default');
})->throws(\InvalidArgumentException::class, "Cannot get undefined Connection 'default', check your spelling or create an entry in your database connections list.");
// can create a connection from dsn
test('can create a connection from dsn', function () {

    $f = new Factory([
        'use' => 'default',
        'connections' => [

        ]
    ]);

    $c = $f->create('sqlite::memory:');

    expect($c)->toBeInstanceOf(Connection::class);
    expect($c->getName())->toBe('memory');

});
