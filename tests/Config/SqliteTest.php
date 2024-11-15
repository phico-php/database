<?php

use Phico\Database\Connection\Config;

$defaults = [
    "driver" => null,
    "database" => null,
    "host" => null,
    "port" => null,
    "socket" => null,
    "charset" => null,
    "username" => null,
    "password" => null,
    "options" => [],
];

// test valid SQLite config against DSN
test("Valid SQLite configs are accepted", function ($dsn, $config) {
    $c = Config::fromArray($config);
    expect($c)->toBeInstanceOf(Config::class);
    expect($c->getDsn())->toBe($dsn);
})->with([
            "home folder" => [
                "sqlite:~/test.db",
                ["driver" => "sqlite", "database" => "~/test.db"],
            ],
            "nested folder" => [
                "sqlite:/home/dev/test.db",
                ["driver" => "sqlite", "database" => "/home/dev/test.db"],
            ],
            "memory" => [
                "sqlite::memory:",
                ["driver" => "sqlite", "database" => ":memory:"],
            ],
        ]);

// test valid SQLite DSN against config
test("Valid SQLite DSN is accepted", function ($dsn, $config) use ($defaults) {
    $c = Config::fromDsn($dsn);
    expect($c)->toBeInstanceOf(Config::class);
    expect($c->toArray())->toEqual(array_merge($defaults, $config));
})->with([
            "home folder" => [
                "sqlite:~/test.db",
                ["driver" => "sqlite", "database" => "~/test.db"],
            ],
            "nested folder" => [
                "sqlite:/home/dev/test.db",
                ["driver" => "sqlite", "database" => "/home/dev/test.db"],
            ],
            "memory" => [
                "sqlite::memory:",
                ["driver" => "sqlite", "database" => ":memory:"],
            ],
        ]);

// test invalid SQLite config throw exceptions
test("Invalid SQLite configs throw exceptions", function ($message, $config) {
    try {
        Config::fromArray($config);
    } catch (Throwable $th) {
        expect($th)->toBeInstanceOf(InvalidArgumentException::class);
        expect($th->getMessage())->toBe($message);
    }
})->with([
            "empty" => [
                "Cannot create Config from array as it is missing the driver name",
                [],
            ],
            "missing driver" => [
                "Cannot create Config from array as it is missing the driver name",
                ["driver" => null],
            ],
            "missing database for sqlite" => [
                "Cannot create Config from array as it is missing the database name",
                ["driver" => "sqlite"],
            ],
            "null database for sqlite" => [
                "Cannot create Config from array as it is missing the database name",
                ["driver" => "sqlite", "database" => null],
            ],
        ]);
