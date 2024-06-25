<?php

namespace Tests\Index;

use Phico\Database\Schema\Index;

test('can create an index', function ($expect, $dialect, $unique) {

    $index = new Index($dialect, 'example', ['column1', 'column2'], 'example_index');

    if ($unique) {
        $index->unique();
    }

    expect((string) $index)->toBe($expect);

})->with([
            ['CREATE INDEX example_index ON example (column1, column2);', 'mysql', false],
            ['CREATE UNIQUE INDEX example_index ON example (column1, column2);', 'mysql', true],
            ['CREATE INDEX example_index ON example (column1, column2);', 'pgsql', false],
            ['CREATE UNIQUE INDEX example_index ON example (column1, column2);', 'pgsql', true],
            ['CREATE INDEX example_index ON example (column1, column2);', 'sqlite', false],
            ['CREATE UNIQUE INDEX example_index ON example (column1, column2);', 'sqlite', true],
        ]);

test('can create a foreign key', function ($expect, $dialect) {

    $index = new Index($dialect, 'example', ['column1'], 'example_fk');
    $index->addForeignKey([
        'columns' => ['column1'],
        'referenced_table' => 'other_table',
        'referenced_columns' => ['id']
    ]);

    expect((string) $index)->toBe($expect);

})->with([
            ['CREATE INDEX example_fk ON example (column1); ALTER TABLE example ADD CONSTRAINT example_fk FOREIGN KEY (column1) REFERENCES other_table (id);', 'mysql'],
            ['CREATE INDEX example_fk ON example (column1); ALTER TABLE example ADD CONSTRAINT example_fk FOREIGN KEY (column1) REFERENCES other_table (id);', 'pgsql'],
            ['CREATE INDEX example_fk ON example (column1); ALTER TABLE example ADD FOREIGN KEY (column1) REFERENCES other_table (id);', 'sqlite'],
        ]);
