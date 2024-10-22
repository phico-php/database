<?php

declare(strict_types=1);

namespace Phico\Database;

use BadMethodCallException;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;


class DB
{
    protected PDO $conn;
    protected int $tx_level;
    protected array $attributes;


    /**
     * The DB class requires a PDO instance to connect to the database
     * @param PDO $conn
     */
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
        $this->tx_level = 0;
    }
    /**
     * Returns the specified attribute value
     * @param int $constant The PDO constant for the attribute
     * @return mixed
     */
    public function attr(int $constant): mixed
    {
        try {
            return $this->conn->getAttribute($constant);
        } catch (Throwable $th) {
            return $th->getMessage();
        }
    }
    /**
     * Returns all the PDO connection attributes
     * @return array
     */
    public function attrs(): array
    {
        if (isset($this->attributes) and !empty($this->attributes)) {
            return $this->attributes;
        }

        $this->attributes = [];
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
     * @param string $seq The sequence name, required by Postgres
     * @return string
     */
    public function getInsertId(string $seq = null): string
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

            $stmt = $this->conn->prepare($sql);
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
                    // 'attributes' => $this->getAttributes(),
                ],
                $e
            );

            logger()->error($e->toString(), $e->toArray());

            throw $e;
        }
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
     * Begin a new transaction (nested transactions are ignored)
     * @return void
     */
    public function begin(): void
    {
        $this->tx_level++;
        if ($this->tx_level == 1) {
            $this->conn->beginTransaction();
        }
    }
    /**
     * Commit the active transaction (nested transactions are ignored)
     * @return void
     */
    public function commit(): void
    {
        if ($this->tx_level == 1) {
            $this->conn->commit();
        }
        $this->tx_level--;
    }
    /**
     * Rollback the active transaction (nested transactions are ignored)
     * @return void
     */
    public function rollback(): void
    {
        if ($this->tx_level == 1) {
            $this->conn->rollback();
        }
        $this->tx_level--;
    }

    /**
     * Create a savepoint to rollback to
     * @param string $name The name of the savepoint to create
     * @return self
     */
    public function savepoint(string $name): void
    {
        if ($this->tx_level === 0) {
            throw new BadMethodCallException(sprintf('Cannot use savepoints outside transactions, please start a transaction before calling savepoint %s', $name));
        }

        $this->conn->exec("SAVEPOINT $name");
    }
    /**
     * Rollback to a named savepoint
     * @param string $name The name of the savepoint to rollback to
     * @return self
     */
    public function rollbackTo(string $name): void
    {
        if ($this->tx_level === 0) {
            throw new BadMethodCallException(sprintf('Cannot use savepoints outside transactions, please start a transaction before calling rollback to %s', $name));
        }

        $this->conn->exec("ROLLBACK TO SAVEPOINT $name");
    }
}
