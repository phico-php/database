<?php

use Phico\Database\Connection\Config;

// test unsupported driver throws exception
test('Unsupported driver throws exception', function () {

    try {
        Config::fromArray([
            'driver' => 'mssql',
            'database' => 'test'
        ]);
    } catch (\Throwable $th) {
        expect($th)->toBeInstanceOf(InvalidArgumentException::class);
        expect($th->getMessage())->toBe('Cannot use unsupported driver mssql');
    }

});

// test default ports
test('Default ports are used if not provided', function ($expected, $config) {

    $c = Config::fromArray($config);
    expect($c->getPort())->toBe($expected);

})->with([
            'missing port for mysql uses default 3306' => [
                3306,
                ['driver' => 'mysql', 'database' => 'test.db', 'host' => 'localhost']
            ],
            'missing port for pgsql uses default 5432' => [
                5432,
                ['driver' => 'pgsql', 'database' => 'test.db', 'host' => 'localhost']
            ],
        ]);

test('Can access properties individually', function ($config) {

    $c = Config::fromArray($config);

    foreach ($config as $k => $v) {
        $method = sprintf('get%s', ucfirst($k));
        $method = match ($method) {
            'getUnix_socket' => 'getSocket',
            default => $method
        };
        expect($c->$method())->toBe($v);
    }

})->with([

            'mysql localhost' => [
                [
                    'driver' => 'mysql',
                    'host' => 'localhost',
                    'database' => 'test'
                ]
            ],
            'mysql socket' => [
                [
                    'driver' => 'mysql',
                    'socket' => '/var/tmp/mysql.sock',
                    'database' => 'test',
                    'charset' => 'utf8mb4'
                ]
            ],
            'mysql unix_socket' => [
                [
                    'driver' => 'mysql',
                    'unix_socket' => '/var/tmp/mysql.sock',
                    'database' => 'test',
                    'charset' => 'utf8mb4'
                ]
            ],
            'sqlite file' => [
                [
                    'driver' => 'sqlite',
                    'database' => '~/test.sqlite3'
                ]
            ],
            'sqlite memory' => [
                [
                    'driver' => 'sqlite',
                    'database' => ':memory:'
                ]
            ]

        ]);
test('can get non-dsn fields', function () {


    $c = Config::fromArray([
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'test',
        'username' => 'user',
        'password' => 'secret',
        'options' => [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    ]);

    expect($c->getUsername())->toBe('user');
    expect($c->getPassword())->toBe('secret');
    expect($c->getOptions())->toBeArray()->toBe([
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

});
/*
<?php

use Phico\Database\Connection\Config;

// Test for unsupported driver exception
test('Unsupported driver throws exception', function () {
    try {
        Config::fromArray([
            'driver' => 'mssql',
            'database' => 'test'
        ]);
    } catch (\Throwable $th) {
        expect($th)->toBeInstanceOf(InvalidArgumentException::class);
        expect($th->getMessage())->toBe('Cannot use unsupported driver mssql');
    }
});

// Test for default ports for MySQL and PostgreSQL
test('Default ports are used if not provided', function ($expected, $config) {
    $c = Config::fromArray($config);
    expect($c->toArray()['port'])->toBe($expected);
})->with([
    'missing port for mysql uses default 3306' => [3306, ['driver' => 'mysql', 'database' => 'test.db', 'host' => 'localhost']],
    'missing port for pgsql uses default 5432' => [5432, ['driver' => 'pgsql', 'database' => 'test.db', 'host' => 'localhost']],
]);

// Test individual property access
test('Can access properties individually', function ($config) {
    $c = Config::fromArray($config);
    foreach ($config as $k => $v) {
        $method = sprintf('get%s', ucfirst($k));
        expect($c->$method())->toBe($v);
    }
})->with([
    'mysql localhost' => [['driver' => 'mysql', 'host' => 'localhost', 'database' => 'test']],
    'mysql socket' => [['driver' => 'mysql', 'socket' => '/var/tmp/mysql.sock', 'database' => 'test']],
    'sqlite file' => [['driver' => 'sqlite', 'database' => '~/test.sqlite3']],
    'sqlite memory' => [['driver' => 'sqlite', 'database' => ':memory:']],
]);

// Test DSN generation for MySQL with and without charset
test('Valid MySQL configs are accepted', function ($dsn, $config) {
    $c = Config::fromArray($config);
    expect($c)->toBeInstanceOf(Config::class);
    expect($c->getDsn())->toBe($dsn);
})->with([
    'localhost with default port' => ['mysql:host=localhost;port=3306;dbname=test', ['driver' => 'mysql', 'host' => 'localhost', 'database' => 'test']],
    'localhost with specified port' => ['mysql:host=localhost;port=3307;dbname=test', ['driver' => 'mysql', 'host' => 'localhost', 'port' => 3307, 'database' => 'test']],
    'remote with charset' => ['mysql:host=10.0.0.15;port=3306;dbname=test;charset=LATIN1', ['driver' => 'mysql', 'host' => '10.0.0.15', 'charset' => 'LATIN1', 'database' => 'test']],
    'local socket' => ['mysql:unix_socket=/var/tmp/mysql.sock;dbname=test', ['driver' => 'mysql', 'socket' => '/var/tmp/mysql.sock', 'database' => 'test']],
]);

// Test DSN generation for SQLite
test('Valid SQLite configs are accepted', function ($dsn, $config) {
    $c = Config::fromArray($config);
    expect($c)->toBeInstanceOf(Config::class);
    expect($c->getDsn())->toBe($dsn);
})->with([
    'home folder' => ['sqlite:dbname=~/test.db', ['driver' => 'sqlite', 'database' => '~/test.db']],
    'nested folder' => ['sqlite:dbname=/home/dev/test.db', ['driver' => 'sqlite', 'database' => '/home/dev/test.db']],
    'memory' => ['sqlite::memory:', ['driver' => 'sqlite', 'database' => ':memory:']],
]);

// Test invalid MySQL configurations throw exceptions
test('Invalid MySQL configs throw exceptions', function ($message, $config) {
    try {
        Config::fromArray($config);
    } catch (\Throwable $th) {
        expect($th)->toBeInstanceOf(InvalidArgumentException::class);
        expect($th->getMessage())->toBe($message);
    }
})->with([
    'empty' => ['Cannot create Config from array as it is missing the driver name', []],
    'missing driver' => ['Cannot create Config from array as it is missing the driver name', ['database' => 'test']],
    'missing database' => ['Cannot create Config from array as it is missing the database name', ['driver' => 'mysql']],
    'missing host and socket' => ['Missing host and socket for connection, please provide a hostname or socket to connect to', ['driver' => 'mysql', 'database' => 'test-db']],
]);

// Test invalid SQLite configurations throw exceptions
test('Invalid SQLite configs throw exceptions', function ($message, $config) {
    try {
        Config::fromArray($config);
    } catch (\Throwable $th) {
        expect($th)->toBeInstanceOf(InvalidArgumentException::class);
        expect($th->getMessage())->toBe($message);
    }
})->with([
    'missing driver' => ['Cannot create Config from array as it is missing the driver name', []],
    'missing database for sqlite' => ['Cannot create Config from array as it is missing the database name', ['driver' => 'sqlite']],
    'sqlite with host and socket' => ['SQLite does not require host and port or socket details', ['driver' => 'sqlite', 'host' => 'localhost', 'socket' => '/var/tmp/mysql.sock', 'database' => 'test']],
]);

// Test setting options for PDO
test('Setting PDO options works as expected', function () {
    $config = [
        'driver' => 'mysql',
        'database' => 'test',
        'options' => [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    ];
    $c = Config::fromArray($config);
    expect($c->getOptions())->toBe([PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
});

// Test DSN parsing through fromDsn
test('Valid MySQL DSN is accepted', function ($dsn, $expected) {
    $c = Config::fromDsn($dsn);
    expect($c)->toBeInstanceOf(Config::class);
    expect($c->getDsn())->toBe($dsn);
})->with([
    'mysql:host=localhost;dbname=test' => ['mysql:host=localhost;dbname=test', 'mysql:host=localhost;port=3306;dbname=test'],
    'mysql:unix_socket=/var/tmp/mysql.sock;dbname=test' => ['mysql:unix_socket=/var/tmp/mysql.sock;dbname=test', 'mysql:unix_socket=/var/tmp/mysql.sock;dbname=test'],
]);

// Test charset exception for SQLite
test('SQLite throws exception if charset is set', function () {
    try {
        Config::fromArray(['driver' => 'sqlite', 'database' => ':memory:', 'charset' => 'utf8']);
    } catch (\Throwable $th) {
        expect($th)->toBeInstanceOf(InvalidArgumentException::class);
        expect($th->getMessage())->toBe('SQLite does not require a charset, it is always UTF-8');
    }
});
*/
