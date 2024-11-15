<?php

use Phico\Database\Schema\Index;


test('can drop an index', function ($expect, $dialect) {

    $index = new Index($dialect, 'example', ['column1', 'column2'], 'example_index');
    $index->drop();

    expect($index->toSql())->toBe($expect);

})->with([
            ['DROP INDEX example_index ON example;', 'mysql'],
            ['DROP INDEX example_index;', 'pgsql'],
            ['DROP INDEX example_index;', 'sqlite'],
        ]);

test('can drop an index without a name', function ($expect, $dialect) {

    $index = new Index($dialect, 'example', ['column1', 'column2']);
    $index->drop();

    expect($index->toSql())->toBe($expect);

})->with([
            ['DROP INDEX example_column1_column2_idx ON example;', 'mysql'],
            ['DROP INDEX example_column1_column2_idx;', 'pgsql'],
            ['DROP INDEX example_column1_column2_idx;', 'sqlite'],
        ]);

