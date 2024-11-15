<?php

// this file contains the database config used during tests

return [
    'use' => 'memory',
    'connections' => [
        'memory' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'options' => [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            ]
        ],
        'test' => [
            'driver' => 'sqlite',
            'database' => path('fixtures/test.db'),
            'options' => [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            ]
        ]
    ]
];
