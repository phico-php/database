<?php

namespace Tests\Index;

use Phico\Database\Schema\Foreign;

test('can create a foreign index', function ($expect, $dialect) {

    $index = new Foreign($dialect, 'example', ['column1'], 'example_fk');
    $index->addForeignKey([
        'columns' => ['column1'],
        'referenced_table' => 'other_table',
        'referenced_columns' => ['id']
    ]);

    expect($index->toSql())->toBe($expect);

})->with([
            ['CREATE INDEX example_fk ON example (column1); ALTER TABLE example ADD CONSTRAINT example_fk FOREIGN KEY (column1) REFERENCES other_table (id);', 'mysql'],
            ['CREATE INDEX example_fk ON example (column1); ALTER TABLE example ADD CONSTRAINT example_fk FOREIGN KEY (column1) REFERENCES other_table (id);', 'pgsql'],
            ['CREATE INDEX example_fk ON example (column1); ALTER TABLE example ADD FOREIGN KEY (column1) REFERENCES other_table (id);', 'sqlite'],
        ]);
