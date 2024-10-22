<?php

declare(strict_types=1);

use Phico\Database\DatabaseException;

if (!function_exists('db')) {
    // db requires the name of the connection to use
    function db(string $conn = null): \Phico\Database\DB
    {
        // fetch default connection name if not provided
        $conn = (is_null($conn)) ? config()->get("database.use") : $conn;

        // fetch connection details
        $config = config()->get("database.connections.$conn");

        // create dsn
        $dsn = ($config['driver'] === 'sqlite')
            ? sprintf('%s:%s', $config['driver'], path($config['database']))
            : sprintf('%s:host=%s;port=%s;dbname=%s', $config['driver'], $config['host'], $config['port'], $config['database']);

        // try to create PDO connection using config details
        try {
            $pdo = new PDO($dsn, $config['username'] ?? '', $config['password'] ?? '', $config['options'] ?? []);
            return new \Phico\Database\DB($pdo);
        } catch (PDOException $e) {
            throw new DatabaseException('Failed to connect to database %s', 1005, null, [], [], $e);
        }
    }
}
if (!function_exists('table')) {
    function table(string $dialect = null): \Phico\Database\Schema\Table
    {
        if (is_null($dialect)) {
            $dialect = db()->attr(PDO::ATTR_DRIVER_NAME);
        }
        return new \Phico\Database\Schema\Table($dialect);
    }
}
