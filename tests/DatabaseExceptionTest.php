<?php

use Phico\Database\DatabaseException;

test('DatabaseException correctly sets message and code', function () {
    $exception = new DatabaseException(
        message: 'Database error occurred',
        code: 1001
    );

    expect($exception->getMessage())->toBe('1001:Database error occurred')
        ->and($exception->getCode())->toBe(0); // The code is set to 0 in the parent Exception by default
});

test('DatabaseException correctly stores SQL query, parameters, and connection info', function () {
    $sql = 'SELECT * FROM users WHERE id = :id';
    $params = ['id' => 1];
    $connectionInfo = ['host' => 'localhost', 'database' => 'test_db'];

    $exception = new DatabaseException(
        message: 'Query failed',
        code: 2002,
        sql: $sql,
        params: $params,
        connection_info: $connectionInfo
    );

    expect($exception->toArray())->toBe([
        'code' => 0,
        'message' => '2002:Query failed',
        'sql' => $sql,
        'params' => $params,
        'info' => $connectionInfo
    ]);
});

test('DatabaseException toArray method returns the correct structure', function () {
    $sql = 'INSERT INTO users (name, email) VALUES (:name, :email)';
    $params = ['name' => 'John Doe', 'email' => 'johndoe@example.com'];
    $connectionInfo = ['host' => 'localhost', 'database' => 'production_db'];
    $previous = new Exception("Previous exception");

    $exception = new DatabaseException(
        message: 'Failed to execute query',
        code: 5001,
        sql: $sql,
        params: $params,
        connection_info: $connectionInfo,
        previous: $previous
    );

    $expectedArray = [
        'code' => 0, // Since the code is overridden to 0 in parent Exception
        'message' => '5001:Failed to execute query',
        'sql' => $sql,
        'params' => $params,
        'info' => $connectionInfo
    ];

    expect($exception->toArray())->toBe($expectedArray);
});

test('DatabaseException correctly sets previous exception', function () {
    $previousException = new Exception('Initial error');

    $exception = new DatabaseException(
        message: 'Wrapper exception',
        code: 3001,
        previous: $previousException
    );

    expect($exception->getPrevious())->toBe($previousException);
});
