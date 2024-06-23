<?php

namespace Tests\Column;

use Phico\Schema\Table;


test('can create a table with timestamps', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example');
    $t->timestamps();

    expect(strip($t))->toBe($expect);

})->with([

    ['CREATE TABLE `example` ( `created_at` timestamp null, `updated_at` timestamp null );', 'mysql'],
    ['CREATE TABLE "example" ( "created_at" timestamp null, "updated_at" timestamp null );', 'pgsql'],
    ['CREATE TABLE "example" ( "created_at" integer null, "updated_at" integer null );', 'sqlite'],

]);
