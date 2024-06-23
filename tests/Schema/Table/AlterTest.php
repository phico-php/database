<?php

namespace Tests\Table;

use Phico\Schema\Table;


test('can alter a table', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->alter('example');

    expect(strip($t))->toBe($expect);

})->with([

    ['ALTER TABLE `example` ( );', 'mysql'],
    ['ALTER TABLE "example" ( );', 'pgsql'],
    ['ALTER TABLE "example" ( );', 'sqlite'],

]);
