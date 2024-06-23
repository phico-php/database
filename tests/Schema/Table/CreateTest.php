<?php

namespace Tests\Table;

use Phico\Schema\Table;
use InvalidArgumentException;

test('cannot handle unsupported dialect', function(){

    $this->expectException(InvalidArgumentException::class);

    $t = new Table('mssql');

});
test('can create a table', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example');

    expect(strip($t))->toBe($expect);

})->with([

    ['CREATE TABLE `example` ( );', 'mysql'],
    ['CREATE TABLE "example" ( );', 'pgsql'],
    ['CREATE TABLE "example" ( );', 'sqlite'],

]);
test('can create a table if not exists', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example')->ifNotExists();

    expect(strip($t))->toBe($expect);

})->with([

    ['CREATE TABLE IF NOT EXISTS `example` ( );', 'mysql'],
    ['CREATE TABLE IF NOT EXISTS "example" ( );', 'pgsql'],
    ['CREATE TABLE IF NOT EXISTS "example" ( );', 'sqlite'],

]);
test('can create a table with a primary key', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example');
    $t->column('id')->integer()->primary()->autoIncrement();

    expect(strip($t))->toBe($expect);

})->with([

    ['CREATE TABLE `example` ( `id` integer unsigned not null auto_increment primary key, PRIMARY KEY(id) );', 'mysql'],
    ['CREATE TABLE "example" ( "id" serial, SERIAL(id) );', 'pgsql'],
    ['CREATE TABLE "example" ( "id" integer unsigned not null auto_increment primary key, PRIMARY KEY(id) );', 'sqlite'],

]);
