<?php

use Phico\Database\Schema\Table;

test("can create a table with softDeletes", function ($expect, $dialect) {
    $t = new Table($dialect);
    $t->softDeletes();

    expect(strip($t->toSql()))->toBe($expect);
})->with([
    ["`deleted_at` timestamp null", "mysql"],
    ['"deleted_at" timestamp null', "pgsql"],
    ['"deleted_at" integer null', "sqlite"],
]);
