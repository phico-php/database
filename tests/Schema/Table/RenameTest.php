<?php

namespace Tests\Table;

use Phico\Schema\Table;


test('can rename() a table', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->rename('old', 'new');

    expect(strip($t))->toBe($expect);

})->with([

    ['RENAME TABLE `old` TO `new`;', 'mysql'],
    ['ALTER TABLE "old" RENAME TO "new";', 'pgsql'],
    ['ALTER TABLE "old" RENAME TO "new";', 'sqlite'],

]);
test('can renameIfExists() table', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->renameIfExists('old', 'new');

    expect(strip($t))->toBe($expect);

})->with([

    ['RENAME TABLE IF EXISTS `old` TO `new`;', 'mysql'],
    ['ALTER TABLE IF EXISTS "old" RENAME TO "new";', 'pgsql'],
    ['ALTER TABLE IF EXISTS "old" RENAME TO "new";', 'sqlite'],

]);
test('can rename()->ifExists() table', function ($expect, $dialect) {

    $t = new Table($dialect);
    $t->rename('old', 'new')->ifExists();

    expect(strip($t))->toBe($expect);

})->with([

    ['RENAME TABLE IF EXISTS `old` TO `new`;', 'mysql'],
    ['ALTER TABLE IF EXISTS "old" RENAME TO "new";', 'pgsql'],
    ['ALTER TABLE IF EXISTS "old" RENAME TO "new";', 'sqlite'],

]);
