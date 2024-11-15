<?php

use Phico\Database\Schema\Table;
use InvalidArgumentException;

/*
test('cannot handle unsupported dialect', function () {

    $this->expectException(InvalidArgumentException::class);

    $t = new Table('mssql');

});
test('can create a table', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example');

    expect(strip($t->toSql()))->toBe($expect);

})->with([

            ['CREATE TABLE `example` ( );', 'mysql'],
            ['CREATE TABLE "example" ( );', 'pgsql'],
            ['CREATE TABLE "example" ( );', 'sqlite'],

        ]);
test('can create a table if not exists', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example')->ifNotExists();

    expect(strip($t->toSql()))->toBe($expect);

})->with([

            ['CREATE TABLE IF NOT EXISTS `example` ( );', 'mysql'],
            ['CREATE TABLE IF NOT EXISTS "example" ( );', 'pgsql'],
            ['CREATE TABLE IF NOT EXISTS "example" ( );', 'sqlite'],

        ]);

test('can create a table with a primary key', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example');
    $t->integer('id')->primary();

    expect(strip($t->toSql()))->toBe($expect);

})->with([

            ['CREATE TABLE `example` ( `id` integer unsigned not null primary key, PRIMARY KEY(id) );', 'mysql'],
            ['CREATE TABLE "example" ( "id" serial, SERIAL(id) );', 'pgsql'],
            ['CREATE TABLE "example" ( "id" integer unsigned not null primary key, PRIMARY KEY(id) );', 'sqlite'],

        ]);
*/
test('can create a table with an auto incrementing primary key', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example');
    $t->integer('id')->primary()->autoIncrement();

    expect(strip($t->toSql()))->toBe($expect);

})->with([

            ['CREATE TABLE `example` ( `id` integer unsigned not null primary key auto_increment, PRIMARY KEY (`id`) );', 'mysql'],
            ['CREATE TABLE "example" ( "id" serial, SERIAL("id") );', 'pgsql'],
            ['CREATE TABLE "example" ( "id" integer unsigned not null primary key auto_increment, PRIMARY KEY ("id") );', 'sqlite'],

        ]);
