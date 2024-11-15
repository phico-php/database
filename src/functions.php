<?php

declare(strict_types=1);

if (!function_exists('db')) {
    function db(?string $name = null, ?array $config = null): \Phico\Database\Database
    {
        return new \Phico\Database\Database($name, $config);
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
