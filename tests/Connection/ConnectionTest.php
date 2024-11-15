<?php

use Phico\Database\Connection;

beforeEach(function () {
    $this->params = [
        'dsn' => 'sqlite::memory:',
        'username' => 'testuser',
        'password' => 'testpass',
        'options' => [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    ];
});

// test that a Connection can be constructed from an array of parameters
test('can be constructed from an array', function () {
    $connection = new Connection($this->params);

    expect($connection)->toBeInstanceOf(Connection::class);
});

// test that a Connection can be created from a DSN string
test('can be created from DSN', function () {
    $connection = Connection::fromDsn(
        $this->params['dsn'],
        $this->params['username'],
        $this->params['password'],
        $this->params['options']
    );

    expect($connection)->toBeInstanceOf(Connection::class);
});

// test that the Connection does not connect when created
// test('does not connect when created', function () {
//     $connection = new Connection($this->params);

//     expect($connection->pdo())->toBeNull();
// });

// test that the Connection returns a valid DSN
test('returns valid DSN', function () {
    $connection = new Connection($this->params);

    expect($connection->dsn())->toBe($this->params['dsn']);
});

// test that the Connection returns the username
test('returns username', function () {
    $connection = new Connection($this->params);

    expect($connection->username())->toBe($this->params['username']);
});

// test that the Connection returns the password
test('returns password', function () {
    $connection = new Connection($this->params);

    expect($connection->password())->toBe($this->params['password']);
});

// test that the Connection returns options
test('returns options', function () {
    $connection = new Connection($this->params);

    expect($connection->options())->toBe($this->params['options']);
});

// test that the Connection returns a PDO instance after connecting
test('returns PDO instance after connecting', function () {
    $connection = new Connection($this->params);
    $connection->connect();

    expect($connection->pdo())->toBeInstanceOf(PDO::class);
});

// test that the Connection can connect to an ad-hoc SQLite memory database
test('can connect to an ad-hoc SQLite memory database', function () {
    $connection = Connection::fromDsn('sqlite::memory:');
    $connection->connect();

    expect($connection->pdo())->toBeInstanceOf(PDO::class);
});
