<?php

use Phico\Database\Connection\Config;

$defaults = [
    'driver' => null,
    'database' => null,
    'host' => null,
    'port' => 3306,
    'socket' => null,
    'charset' => null,
    'username' => null,
    'password' => null,
    'options' => []
];


// test valid MySQL config against DSN
test('Valid MySQL configs are accepted', function ($dsn, $config) {

    $c = Config::fromArray($config);
    expect($c)->toBeInstanceOf(Config::class);
    expect($c->getDsn())->toBe($dsn);

})->with([
            'localhost with default port' => [
                'mysql:host=localhost;port=3306;dbname=test',
                ['driver' => 'mysql', 'host' => 'localhost', 'database' => 'test']
            ],
            'localhost with specified port' => [
                'mysql:host=localhost;port=3307;dbname=test',
                ['driver' => 'mysql', 'host' => 'localhost', 'port' => 3307, 'database' => 'test']
            ],
            'remote with specified port' => [
                'mysql:host=10.0.0.15;port=3307;dbname=test',
                ['driver' => 'mysql', 'host' => '10.0.0.15', 'port' => 3307, 'database' => 'test']
            ],
            'remote with default port and charset' => [
                'mysql:host=10.0.0.15;port=3306;dbname=test;charset=LATIN1',
                ['driver' => 'mysql', 'host' => '10.0.0.15', 'charset' => 'LATIN1', 'database' => 'test']
            ],
            'local socket' => [
                'mysql:unix_socket=/var/tmp/mysql.sock;dbname=test',
                ['driver' => 'mysql', 'socket' => '/var/tmp/mysql.sock', 'database' => 'test']
            ],
            'local socket with charset' => [
                'mysql:unix_socket=/var/tmp/mysql.sock;dbname=test;charset=utf8mb4',
                ['driver' => 'mysql', 'socket' => '/var/tmp/mysql.sock', 'database' => 'test', 'charset' => 'utf8mb4']
            ],
        ]);

// test valid MySQL DSN against config
test('Valid MySQL DSN is accepted', function ($dsn, $config) use ($defaults) {

    $c = Config::fromDsn($dsn);
    expect($c)->toBeInstanceOf(Config::class);
    expect($c->toArray())->toEqual(array_merge($defaults, $config));

})->with([
            'localhost with default port' => [
                'mysql:host=localhost;dbname=test',
                ['driver' => 'mysql', 'host' => 'localhost', 'port' => 3306, 'database' => 'test']
            ],
            'localhost with specified port' => [
                'mysql:host=localhost;port=3307;dbname=test',
                ['driver' => 'mysql', 'host' => 'localhost', 'port' => 3307, 'database' => 'test']
            ],
            'remote with specified port' => [
                'mysql:host=10.0.0.15;port=3307;dbname=test',
                ['driver' => 'mysql', 'host' => '10.0.0.15', 'port' => 3307, 'database' => 'test']
            ],
            'remote with default port and charset' => [
                'mysql:host=10.0.0.15;port=3306;dbname=test;charset=LATIN1',
                ['driver' => 'mysql', 'host' => '10.0.0.15', 'port' => 3306, 'charset' => 'LATIN1', 'database' => 'test']
            ],
            'local socket' => [
                'mysql:unix_socket=/var/tmp/mysql.sock;dbname=test',
                ['driver' => 'mysql', 'socket' => '/var/tmp/mysql.sock', 'database' => 'test']
            ],
            'local socket with charset' => [
                'mysql:unix_socket=/var/tmp/mysql.sock;dbname=test;charset=utf8mb4',
                ['driver' => 'mysql', 'socket' => '/var/tmp/mysql.sock', 'database' => 'test', 'charset' => 'utf8mb4']
            ],
        ]);

// test invalid MySQL config throw exceptions
test('Invalid MySQL configs throw exceptions', function ($message, $config) {

    try {
        Config::fromArray($config);
    } catch (\Throwable $th) {
        expect($th)->toBeInstanceOf(InvalidArgumentException::class);
        expect($th->getMessage())->toBe($message);
    }

})
    ->with([
        'empty' => [
            'Cannot create Config from array as it is missing the driver name',
            []
        ],
        'missing driver' => [
            'Cannot create Config from array as it is missing the driver name',
            ['driver' => null]
        ],
        'missing database' => [
            'Cannot create Config from array as it is missing the database name',
            ['driver' => 'mysql']
        ],
        'null database' => [
            'Cannot create Config from array as it is missing the database name',
            ['driver' => 'mysql', 'database' => null]
        ],
        'missing host and socket' => [
            'Missing host and socket for connection, please provide a hostname or socket to connect to',
            ['driver' => 'mysql', 'database' => 'test-db']
        ],
        'provided host and socket' => [
            'Passed host and socket for connection, use one or the other not both',
            ['driver' => 'mysql', 'database' => 'test-db', 'host' => 'localhost', 'socket' => '/var/tmp/mysql.sock']
        ],
    ]);

