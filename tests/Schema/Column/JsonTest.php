<?php

namespace Tests\Column;

use Phico\Database\Schema\Column;

test("can create a JSON column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->json();
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` json not null", "mysql"],
    ['"name" json not null', "pgsql"],
    ['"name" text not null', "sqlite"],
]);

test("can create a JSONB column (Postgres only)", function () {
    $c = new Column("pgsql", "name");
    $c->jsonb();
    expect(strip($c->toSql()))->toBe('"name" jsonb not null');
});
