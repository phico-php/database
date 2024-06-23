<?php

namespace Tests\Column;

use Phico\Schema\Table;


test('can create a table with softDeletes', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example');
    $t->softDeletes();

    expect(strip($t))->toBe($expect);

})->with([

    ['CREATE TABLE `example` ( `deleted_at` timestamp null );', 'mysql'],
    ['CREATE TABLE "example" ( "deleted_at" timestamp null );', 'pgsql'],
    ['CREATE TABLE "example" ( "deleted_at" integer null );', 'sqlite'],

]);
