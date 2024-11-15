<?php

use Phico\Database\Schema\Column;

test("can create an auto-increment column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->integer()->autoIncrement();
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` integer unsigned not null auto_increment", "mysql"],
    ['"name" serial', "pgsql"],
    ['"name" integer not null autoincrement', "sqlite"],
]);

test("can create a primary key column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->integer()->primary();
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` integer unsigned not null primary key", "mysql"],
    ['"name" integer not null', "pgsql"],
    ['"name" integer not null primary key', "sqlite"],
]);

test("can create an unsigned integer column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->integer()->unsigned();
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` integer unsigned not null", "mysql"],
    ['"name" integer not null', "pgsql"],
    ['"name" integer not null', "sqlite"],
]);

test("can create a column with a default value", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->integer()->default(100);
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` integer not null default 100", "mysql"],
    ['"name" integer not null default 100', "pgsql"],
    ['"name" integer not null default 100', "sqlite"],
]);

test("can create a column with a comment", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->integer()->comment("This is a comment");
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ['`name` integer not null comment "This is a comment"', "mysql"],
    ['"name" integer not null comment "This is a comment"', "pgsql"],
    ['"name" integer not null comment "This is a comment"', "sqlite"],
]);
