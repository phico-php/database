<?php

namespace Tests\Table;

use Phico\Schema\Table;


test('can drop() a table', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->drop('example');

    expect(strip($t))->toBe($expect);

})->with([

    ['DROP TABLE `example`;', 'mysql'],
    ['DROP TABLE "example";', 'pgsql'],
    ['DROP TABLE "example";', 'sqlite'],

]);
test('can dropIfExists() table', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->dropIfExists('example');

    expect(strip($t))->toBe($expect);

})->with([

    ['DROP TABLE IF EXISTS `example`;', 'mysql'],
    ['DROP TABLE IF EXISTS "example";', 'pgsql'],
    ['DROP TABLE IF EXISTS "example";', 'sqlite'],

]);
test('can drop()->ifExists() table', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->drop('example')->ifExists();

    expect(strip($t))->toBe($expect);

})->with([

    ['DROP TABLE IF EXISTS `example`;', 'mysql'],
    ['DROP TABLE IF EXISTS "example";', 'pgsql'],
    ['DROP TABLE IF EXISTS "example";', 'sqlite'],

]);
