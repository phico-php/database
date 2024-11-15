<?php

use Phico\Database\Schema\Index;


test('can create an index with a name', function ($expect, $dialect, $unique) {

    $index = new Index($dialect, 'example', ['column1', 'column2'], 'custom_column1_column2_idx');

    if ($unique) {
        $index->unique();
    }

    expect($index->toSql())->toBe($expect);

})->with([
            ['CREATE INDEX custom_column1_column2_idx ON example (column1, column2);', 'mysql', false],
            ['CREATE UNIQUE INDEX custom_column1_column2_idx ON example (column1, column2);', 'mysql', true],
            ['CREATE INDEX custom_column1_column2_idx ON example (column1, column2);', 'pgsql', false],
            ['CREATE UNIQUE INDEX custom_column1_column2_idx ON example (column1, column2);', 'pgsql', true],
            ['CREATE INDEX custom_column1_column2_idx ON example (column1, column2);', 'sqlite', false],
            ['CREATE UNIQUE INDEX custom_column1_column2_idx ON example (column1, column2);', 'sqlite', true],
        ]);

test('can create an index without a name', function ($expect, $dialect, $unique) {

    $index = new Index($dialect, 'example', ['column1', 'column2']);

    if ($unique) {
        $index->unique();
    }

    expect($index->toSql())->toBe($expect);

})->with([
            ['CREATE INDEX example_column1_column2_idx ON example (column1, column2);', 'mysql', false],
            ['CREATE UNIQUE INDEX example_column1_column2_idx ON example (column1, column2);', 'mysql', true],
            ['CREATE INDEX example_column1_column2_idx ON example (column1, column2);', 'pgsql', false],
            ['CREATE UNIQUE INDEX example_column1_column2_idx ON example (column1, column2);', 'pgsql', true],
            ['CREATE INDEX example_column1_column2_idx ON example (column1, column2);', 'sqlite', false],
            ['CREATE UNIQUE INDEX example_column1_column2_idx ON example (column1, column2);', 'sqlite', true],
        ]);
