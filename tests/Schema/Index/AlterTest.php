<?php

namespace Tests\Index;

use Phico\Database\Schema\Index;

test('can rename an index', function ($expect, $dialect) {

    $index = new Index($dialect, 'example', ['column1', 'column2'], 'example_index');
    $index->rename('example_index', 'new_example_index');

    expect((string) $index)->toBe($expect);

})->with([
            ['ALTER TABLE example RENAME INDEX example_index TO new_example_index;', 'mysql'],
            ['ALTER INDEX example_index RENAME TO new_example_index;', 'pgsql'],
            ['DROP INDEX example_index; CREATE INDEX new_example_index ON example (column1, column2);', 'sqlite'],
        ]);


