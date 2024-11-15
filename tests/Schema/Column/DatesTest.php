<?php

namespace Tests\Column;

use Phico\Database\Schema\Column;

test("can create a date column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->date();
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` date not null", "mysql"],
    ['"name" date not null', "pgsql"],
    ['"name" text not null', "sqlite"],
]);

test("can create a datetime column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->datetime();
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` datetime not null", "mysql"],
    ['"name" datetime not null', "pgsql"],
    ['"name" text not null', "sqlite"],
]);

test("can create a timestamp column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->timestamp();
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` timestamp not null", "mysql"],
    ['"name" timestamp not null', "pgsql"],
    ['"name" integer not null', "sqlite"],
]);
