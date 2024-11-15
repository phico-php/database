<?php

declare(strict_types=1);

// with this defined the database config will come from ./config/database.php
define('PHICO_PATH_ROOT', __DIR__);

test('can connect to sqlite in memory database', function () {

    $db = db();

    $db->use('memory');

    expect($db->using())->toBe('memory');

    // connections are lazy loaded so force the connection here
    expect($db->attrs())->toBeArray();
    expect($db->attr(PDO::ATTR_DRIVER_NAME))->toBe('sqlite');

});

test('can connect to sqlite file database', function () {

    $db = db();

    $db->use('test');

    expect($db->using())->toBe('test');

    // connections are lazy loaded so force the connection here
    expect($db->attrs())->toBeArray();
    expect($db->attr(PDO::ATTR_DRIVER_NAME))->toBe('sqlite');

});

test('can switch connections', function () {

    $db = db();

    $db->use('test');

    expect($db->using())->toBe('test');
    // connections are lazy loaded so force the connection here
    expect($db->attrs())->toBeArray();
    expect($db->attr(PDO::ATTR_DRIVER_NAME))->toBe('sqlite');

    $db->use('memory');

    expect($db->using())->toBe('memory');
    // connections are lazy loaded so force the connection here
    expect($db->attrs())->toBeArray();
    expect($db->attr(PDO::ATTR_DRIVER_NAME))->toBe('sqlite');

    $db->use('test');

    expect($db->using())->toBe('test');

});

test('cannot switch connections while in transaction', function () {


    $db = db();

    $db->use('test');

    expect($db->using())->toBe('test');
    // connections are lazy loaded so force the connection here
    expect($db->attrs())->toBeArray();

    $db->begin();

    $db->use('memory');


})->throws(\BadMethodCallException::class);

test('can create adhoc connection', function () {

    // call without args
    $db = db();

    $db->connect('sqlite::memory:');

    expect($db->using())->toBe('sqlite::memory:');
    expect($db->attr(PDO::ATTR_DRIVER_NAME))->toBe('sqlite');

});

test('can create table, insert and fetch data', function () {

    $db = db();

    // create a test table
    $db->raw('CREATE TABLE users (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL
    );');

    // insert some data
    $db->execute('INSERT INTO users (name, email) VALUES ("Bob", "bob@example.com")');

    // fetch some data
    $stmt = $db->execute('SELECT * FROM users');

    expect($stmt)->toBeInstanceOf(\PDOStatement::class);

    $rows = $stmt->fetchAll();

    expect($rows)->toBeArray()->toHaveCount(1);

    $obj = $rows[0];

    expect($obj->name)->toBe('Bob');
    expect($obj->email)->toBe('bob@example.com');
});
