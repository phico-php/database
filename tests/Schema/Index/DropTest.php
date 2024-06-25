<?php

namespace Tests\Index;

use Phico\Database\Schema\Index;

test('can drop an index', function ($expect, $dialect) {

    $index = new Index($dialect, 'example', ['column1', 'column2'], 'example_index');
    $index->drop();

    expect((string) $index)->toBe($expect);

})->with([
            ['DROP INDEX example_index ON example;', 'mysql'],
            ['DROP INDEX example_index;', 'pgsql'],
            ['DROP INDEX example_index;', 'sqlite'],
        ]);

