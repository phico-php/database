<?php

namespace Tests\Column;

use Phico\Database\Schema\Column;

test("can create an integer column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->integer();
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` integer not null", "mysql"],
    ['"name" integer not null', "pgsql"],
    ['"name" integer not null', "sqlite"],
]);

test("can create a tiny integer column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->tinyInt();
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` tinyint not null", "mysql"],
    ['"name" smallint not null', "pgsql"],
    ['"name" integer not null', "sqlite"],
]);

test("can create a small integer column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->smallInt();
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` smallint not null", "mysql"],
    ['"name" smallint not null', "pgsql"],
    ['"name" integer not null', "sqlite"],
]);

test("can create a medium integer column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->mediumInt();
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` mediumint not null", "mysql"],
    ['"name" int not null', "pgsql"],
    ['"name" integer not null', "sqlite"],
]);

test("can create a big integer column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->bigInt();
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` bigint not null", "mysql"],
    ['"name" bigint not null', "pgsql"],
    ['"name" integer not null', "sqlite"],
]);
