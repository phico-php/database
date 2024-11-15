<?php

use Phico\Database\Schema\Column;

test("can drop a column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->drop();
    expect(strip($c->toSql()))->toBe($expect);
})->with([["`name`", "mysql"], ['"name"', "pgsql"], ['"name"', "sqlite"]]);

test("can rename a column", function ($expect, $dialect) {
    $c = new Column($dialect, "name");
    $c->rename("new_name");
    expect(strip($c->toSql()))->toBe($expect);
})->with([
    ["`name` TO `new_name`", "mysql"],
    ['"name" TO "new_name"', "pgsql"],
    ['"name" TO "new_name"', "sqlite"],
]);
