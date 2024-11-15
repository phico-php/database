<?php

use Phico\Database\Commands\Seeds;
use Phico\Cli\Args;
use Phico\Database\Database;
use Phico\Database\Schema\Seed;
use Mockery as m;

// with this defined the database config will come from ./config/database.php
define('PHICO_PATH_ROOT', __DIR__);

beforeEach(function () {
    $this->dbMock = m::mock(Database::class);
    $this->argsMock = m::mock(Args::class);

    // Mock configuration helpers
    config()->shouldReceive('get')
        ->with('database.seeds.path', 'resources/seeds')
        ->andReturn('resources/seeds');
    config()->shouldReceive('get')
        ->with('database.seeds.connection', 'default')
        ->andReturn('default');

    // Mock db helper function
    db()->shouldReceive('attr')->andReturn($this->dbMock);

    // Create instance of Seeds class
    $this->seeds = new Seeds();
});

test('create method throws exception when no name is provided', function () {
    $this->argsMock->shouldReceive('index')->with(0)->andReturn(null);
    $this->argsMock->shouldReceive('value')->with('name')->andReturn(null);

    $this->seeds->create($this->argsMock);
})->throws(InvalidArgumentException::class, 'Please provide the name of the seed to create');

test('create method writes seed file if name is provided', function () {
    $this->argsMock->shouldReceive('index')->with(0)->andReturn('TestSeed');
    $this->argsMock->shouldReceive('value')->with('name')->andReturn(null);

    $filesMock = m::mock();
    $filesMock->shouldReceive('exists')->andReturn(false);
    $filesMock->shouldReceive('write')->with(m::type('string'))->once();

    files()->shouldReceive('path')->andReturn($filesMock);

    $this->seeds->create($this->argsMock);
    expect(true)->toBeTrue();
});

test('list method writes "No seeds found" if directory is empty', function () {
    folders()->shouldReceive('list')->andReturn([]);

    $this->seeds->list($this->argsMock);

    $this->expectOutputString("No seeds found in 'resources/seeds'\n");
});

test('list method outputs seed filenames if seeds are present', function () {
    folders()->shouldReceive('list')->andReturn(['SeedA.php', 'SeedB.php']);

    $this->seeds->list($this->argsMock);

    $this->expectOutputString("SeedA.php\nSeedB.php\n");
});

test('run method throws exception when no name is provided', function () {
    $this->argsMock->shouldReceive('index')->with(4)->andReturn(null);
    $this->argsMock->shouldReceive('value')->with('name')->andReturn(null);

    $this->seeds->run($this->argsMock);
})->throws(InvalidArgumentException::class, 'Please provide the name of the seed to run');

test('run method throws error if seed file does not exist', function () {
    $this->argsMock->shouldReceive('index')->with(4)->andReturn('NonExistentSeed.php');
    $this->argsMock->shouldReceive('value')->with('name')->andReturn(null);

    files()->shouldReceive('exists')->andReturn(false);

    $this->seeds->run($this->argsMock);

    $this->expectOutputString("\nCannot find seed file 'NonExistentSeed.php' in 'resources/seeds'\n");
});

test('instantiate method throws exception if class name cannot be parsed', function () {
    files()->shouldReceive('read')->andReturn("This is a test file without a class declaration");

    $this->seeds->instantiate('InvalidSeed.php');
})->throws(RuntimeException::class, 'Cannot get class name from seed file at resources/seeds/InvalidSeed.php');

test('instantiate method returns a Seed instance with correct class name', function () {
    files()->shouldReceive('read')->andReturn("final class TestSeed");
    require_once path("resources/seeds/TestSeed.php");

    $seed = $this->seeds->instantiate('TestSeed.php');
    expect($seed)->toBeInstanceOf(Seed::class);
});

test('getTemplate method returns correct template structure', function () {
    $template = $this->seeds->getTemplate('TestSeed');

    expect($template)->toContain('namespace App\Resources\Seeds;')
        ->toContain('final class TestSeed extends Seed')
        ->toContain('public function seed(): void');
});
