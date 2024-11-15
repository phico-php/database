<?php

namespace Tests\Column;

use Phico\Database\Schema\Column;

test("can create a varchar column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->varchar(255);
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` varchar(255) not null", "mysql"],
    ['"name" varchar(255) not null', "pgsql"],
    ['"name" text not null', "sqlite"],
]);

test("can create a text column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->text();
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` text(65535) not null", "mysql"],
    ['"name" text(65535) not null', "pgsql"],
    ['"name" text not null', "sqlite"],
]);
