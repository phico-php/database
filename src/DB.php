<?php

declare(strict_types=1);

namespace Phico\Database;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;


class DB
{
    protected PDO $conn;
    public readonly string $driver;
    protected int $tx_level;
    protected array $attributes;


    /**
     * The DB class requires a PDO instance to connect to the database
     * @param PDO $conn
     */
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
        $this->driver = $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->tx_level = 0;
    }
    public function getAttribute(int $constant): mixed
    {
        try {
            return $this->conn->getAttribute($constant);
        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }
    /**
     * Returns the PDO connection attributes
     * @return array
     */
    public function getAttributes(): array
    {
        if (is_array($this->attributes) && !empty($this->attributes)) {
            return $this->attributes;
        }

        $$this->attributes = [];
        $attrs = [
            "AUTOCOMMIT",
            "CASE",
            "CLIENT_VERSION",
            "CONNECTION_STATUS",
            "DEFAULT_FETCH_MODE",
            "DEFAULT_STR_PARAM",
            "DRIVER_NAME",
            "EMULATE_PREPARES",
            "ERRMODE",
            "PERSISTENT",
            "PREFETCH",
            "SERVER_INFO",
            "SERVER_VERSION",
            "STRINGIFY_FETCHES",
            "TIMEOUT",
        ];
        foreach ($attrs as $attr) {
            try {
                $this->attributes[$attr] = $this->conn->getAttribute(constant("PDO::ATTR_$attr"));
            } catch (Throwable $th) {
                $this->attributes[$attr] = $th->getMessage();
            }
        }

        return $this->attributes;
    }
    /**
     * Returns the last insert id from the internal PDOStatement pointer
     * @param string $seq The PgSQL sequence name
     * @return string
     */
    public function lastInsertId(string $seq = null): string
    {
        return $this->conn->lastInsertId($seq);
    }
    /**
     * Prepares and executes an SQL query binding the $params safely
     * @param string $sql The parameterised SQL query
     * @param array<string,mixed> $params The parameter values to be escaped
     * @return PDOStatement
     * @throws DatabaseException
     */
    public function execute(string $sql, ?array $params = []): PDOStatement
    {
        try {

            $stmt = $this->prepareStatement($sql);
            $stmt->execute($params);

            return $stmt;

        } catch (PDOException $e) {

            if (isset($stmt)) {
                $stmt->closeCursor();
            }

            $e = new DatabaseException(
                $e->getMessage(),
                $e->getCode(),
                $sql,
                $params,
                [
                    'errorCode' => $this->conn->errorCode(),
                    'errorInfo' => $this->conn->errorInfo(),
                ],
                $e
            );

            logger()->error($e->toString(), $e->toArray());

            throw $e;
        }
    }
    /**
     * Returns a single row as an object, or null if no rows found
     * @param \Phico\Query\Query $query The query to execute
     * @return null|object
     */
    public function fetchOne(Query $query): ?object
    {
        $query->limit(1);
        $stmt = $this->execute($query->toSql($this->driver), $query->getParams());
        $row = $stmt->fetch();

        return (false === $row) ? null : $row;
    }
    /**
     * Returns an array of matching rows, or an empty array if no rows found
     * @param \Phico\Query\Query $query The query to execute
     * @return array
     */
    public function fetchMany(Query $query): array
    {
        $stmt = $this->execute($query->toSql($this->driver), $query->getParams());
        $rows = $stmt->fetchAll();

        return (false === $rows) ? [] : $rows;
    }
    /**
     * Prepares and executes a Query instance safely
     * @param Query $query The Query instance
     * @return PDOStatement
     * @throws DatabaseException
     */
    public function query(Query $query): PDOStatement
    {
        return $this->execute($query->toSql($this->driver), $query->getParams());
    }
    /**
     * Executes a raw SQL query using unsafe input and can execute multiple queries
     * @param string $sql The parameterised SQL query
     * @return int The number of affected rows
     * @throws DatabaseException
     */
    public function raw(string $sql): int
    {
        try {

            return $this->conn->exec($sql);

        } catch (PDOException $e) {

            $e = new DatabaseException(
                $e->getMessage(),
                $e->getCode(),
                $sql,
                [],
                [
                    'errorCode' => $this->conn->errorCode(),
                    'errorInfo' => $this->conn->errorInfo(),
                ],
                $e
            );

            logger()->error($e->toString(), $e->toArray());

            throw $e;
        }
    }
    /**
     * Start a new transaction
     * @return void
     */
    public function startTransaction(): void
    {
        $this->tx_level++;
        if ($this->tx_level == 1) {
            $this->conn->beginTransaction();
        }
    }
    /**
     * Commit the current transaction
     * @return void
     */
    public function finishTransaction(): void
    {
        if ($this->tx_level == 1) {
            $this->conn->commit();
        }
        $this->tx_level--;
    }
    /**
     * Cancel the current transaction
     * @return void
     */
    public function cancelTransaction(): void
    {
        if ($this->tx_level == 1) {
            $this->conn->rollback();
        }
        $this->tx_level--;
    }

    /**
     * Prepares a PDOStatement.
     * @param string $sql
     * @return PDOStatement
     */
    private function prepareStatement(string $sql): PDOStatement
    {
        return $this->conn->prepare($sql);
    }
}
