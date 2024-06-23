<?php

declare(strict_types=1);

if (!function_exists('db')) {
    // db requires the name of the connection to use
    function db(string $conn = null): \Phico\Database\DB
    {
        // fetch default connection name if not provided
        $conn = (is_null($conn)) ? config("database.use") : $conn;

        // fetch connection details
        $config = (object) config("database.connections.$conn");

        // try to create PDO connection using config details
        try {
            $pdo = new PDO($config->dsn, $config->username, $config->password, $config->options = []);
            return new \Phico\Database\DB($pdo);
        } catch (PDOException $e) {
            logger()->error(sprintf('Failed to connect to the database, %s in %s line %d', $e->getMessage(), $e->getFile(), $e->getLine()));
            throw $e;
        }
    }
}
