<?php

declare(strict_types=1);

namespace Phico\Database;

use Exception;
use Throwable;


class DatabaseException extends Exception
{
    protected string $sql;
    protected array $params;
    protected array $connection_info;


    public function __construct(
        string $message,
        string|int $code = null,
        string $sql = null,
        array $params = [],
        array $connection_info = [],
        Throwable $previous = null
    ) {
        parent::__construct("$code:$message", 0, $previous);
        $this->sql = $sql;
        $this->params = $params;
        $this->connection_info = $connection_info;
    }
    public function toArray(): array
    {
        return [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'sql' => $this->sql,
            'params' => $this->params,
            'info' => $this->connection_info
        ];
    }
}
