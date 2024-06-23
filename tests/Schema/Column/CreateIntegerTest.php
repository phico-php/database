<?php

namespace Tests\Column;

use Phico\Schema\Table;


test('can create an integer column', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example');
    $t->column('name')->integer();

    expect(strip($t))->toBe($expect);

})->with([

    ['CREATE TABLE `example` ( `name` int not null );', 'mysql'],
    ['CREATE TABLE "example" ( "name" integer not null );', 'pgsql'],
    ['CREATE TABLE "example" ( "name" integer not null );', 'sqlite'],

]);
test('can create an integer column with size', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example');
    $t->column('name')->integer(4);

    expect(strip($t))->toBe($expect);

})->with([

    ['CREATE TABLE `example` ( `name` int(4) not null );', 'mysql'],
    ['CREATE TABLE "example" ( "name" integer not null );', 'pgsql'],
    ['CREATE TABLE "example" ( "name" integer not null );', 'sqlite'],

]);
test('can create a nullable integer column', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example');
    $t->column('name')->integer()->nullable();

    expect(strip($t))->toBe($expect);

})->with([

    ['CREATE TABLE `example` ( `name` int null );', 'mysql'],
    ['CREATE TABLE "example" ( "name" integer null );', 'pgsql'],
    ['CREATE TABLE "example" ( "name" integer null );', 'sqlite'],

]);
test('can create an integer column not nullable', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example');
    $t->column('name')->integer()->notNull();

    expect(strip($t))->toBe($expect);

})->with([

    ['CREATE TABLE `example` ( `name` int not null );', 'mysql'],
    ['CREATE TABLE "example" ( "name" integer not null );', 'pgsql'],
    ['CREATE TABLE "example" ( "name" integer not null );', 'sqlite'],

]);
test('can create an integer column with a default', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example');
    $t->column('name')->integer()->default(100);

    expect(strip($t))->toBe($expect);

})->with([

    ['CREATE TABLE `example` ( `name` int not null default 100 );', 'mysql'],
    ['CREATE TABLE "example" ( "name" integer not null default 100 );', 'pgsql'],
    ['CREATE TABLE "example" ( "name" integer not null default 100 );', 'sqlite'],

]);
test('can create an integer column with a comment', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->create('example');
    $t->column('name')->integer()->comment("This is a comment");

    expect(strip($t))->toBe($expect);

})->with([

    ['CREATE TABLE `example` ( `name` int not null comment "This is a comment" );', 'mysql'],
    ['CREATE TABLE "example" ( "name" integer not null comment "This is a comment" );', 'pgsql'],
    ['CREATE TABLE "example" ( "name" integer not null comment "This is a comment" );', 'sqlite'],

]);
