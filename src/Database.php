<?php

declare(strict_types=1);

namespace Phico\Database;

use BadMethodCallException;
use PDOException;
use PDOStatement;
use Phico\Database\Connection\{Connection, Factory};


/**
 * Handles database interactions, sql statement execution, transactions and savepoints.
 *
 * @package Phico\Database
 * @category Database
 * @license BSD-3-Clause
 * @author indgy@phico-php.net
 */
class Database
{
    /**
     * The active Connection instance
     * @var Connection
     */
    protected Connection $connection;
    /**
     * The Connection Factory instance
     * @var Factory
     */
    protected Factory $factory;


    /**
     * Database will create a Connection to the default database,
     * pass a connection name to connect to a different database.
     * @param ?string $name An optional connection name
     * @param ?array $config An optional database config
     */
    public function __construct(?string $name = null, ?array $config = null)
    {
        $this->factory = new Factory($config);
        $this->connection = $this->factory->get($name);
    }
    /**
     * Returns the specified attribute value
     * This method passes through to Connection.
     * @param int $constant The PDO constant for the attribute
     * @return mixed
     */
    public function attr(int $constant): mixed
    {
        return $this->connection()->attr($constant);
    }
    /**
     * Returns all the PDO connection attributes
     * This method passes through to Connection.
     * @return array
     */
    public function attrs(): array
    {
        return $this->connection()->attrs();
    }
    /**
     * Creates an ad hoc connection to a database
     * @param string $dsn The full Data Source Name connection string
     * @param ?string $username The username to connect as
     * @param ?string $password The user password
     * @param ?array $options An array of options to pass to PDO
     * @return self
     */
    public function connect(string $dsn, ?string $username = null, ?string $password = null, ?array $options = []): self
    {
        // ensure there are no transactions in progress
        if ($this->connection()->inTransaction()) {
            throw new BadMethodCallException("Cannot switch connections during a transaction");
        }
        $this->connection = $this->factory->create($dsn, $username, $password, $options);
        // $this->connection = Connection::fromDsn($dsn, $username, $password, $options);
        // $this->connection_name = $dsn;

        return $this;
    }
    /**
     * Switches to a pre-configured connection by name
     * @param string $name The name of the connection to use
     * @throws BadMethodCallException If a transaction is in progress
     * @return self
     */
    public function use(string $name): self
    {
        // ensure there are no transactions in progress
        if ($this->connection()->inTransaction()) {
            throw new BadMethodCallException("Cannot switch connections during a transaction");
        }

        // fetch the connection from the factory
        $this->connection = $this->factory->get($name);

        return $this;
    }
    /**
     * Returns the current connection name
     * @return string
     */
    public function using(): string
    {
        return $this->connection()->getName();
    }
    /**
     * Returns the last insert id from the internal PDOStatement pointer
     * @param string $seq The sequence name as required by PostgreSQL
     * @return string
     */
    public function getInsertId(string $seq = null): string
    {
        return $this->connection()->pdo()->lastInsertId($seq);
    }
    /**
     * Prepares and executes an SQL query ensuring params are bound safely
     * @param string $sql The parameterised SQL query
     * @param array<string,mixed> $params The parameter values to be escaped
     * @return PDOStatement
     * @throws DatabaseException
     */
    public function execute(string $sql, ?array $params = []): PDOStatement
    {
        try {
            $stmt = $this->connection()->pdo()->prepare($sql);
            $stmt->execute($params);

            return $stmt;
        } catch (PDOException $e) {
            if (isset($stmt)) {
                $stmt->closeCursor();
            }

            throw $this->createException($e, $sql, $params);
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
            return $this->connection()->pdo()->exec($sql);
        } catch (PDOException $e) {
            throw $this->createException($e, $sql);
        }
    }
    /**
     * Begin a new transaction (nested transactions are ignored).
     * This method passes through to Connection to track transaction nesting
     * @return void
     */
    public function begin(): void
    {
        $this->connection()->begin();
    }
    /**
     * Commit the active transaction (nested transactions are ignored).
     * This method passes through to Connection to track transaction nesting
     * @return void
     */
    public function commit(): void
    {
        $this->connection()->commit();
    }
    /**
     * Rollback the active transaction (nested transactions are ignored).
     * This method passes through to Connection to track transaction nesting
     * @return void
     */
    public function rollback(): void
    {
        $this->connection()->rollback();
    }
    /**
     * Create a savepoint to rollback to.
     * This method passes through to Connection to track transaction nesting
     * @param string $name The name of the savepoint to create
     * @return void
     */
    public function savepoint(string $name): void
    {
        $this->connection()->rollback();
    }
    /**
     * Rollback to a named savepoint.
     * This method passes through to Connection to track transaction nesting
     * @param string $name The name of the savepoint to rollback to
     * @return bool Returns true on success, false on failure
     */
    public function rollbackTo(string $name): bool
    {
        return $this->connection()->rollbackTo($name);
    }

    /**
     * Returns the active Connection instance.
     * This is a protected method as we're using this Database class as a Facade
     * to the Connection and PDO instances.
     * @return Connection
     */
    protected function connection(): Connection
    {
        return $this->connection;
    }
    /**
     * Returns a DatabaseException containing the PDOException, query args and connection info
     * @param PDOException $e The PDOEexception instance
     * @param string $sql The SQL query statement
     * @param ?array $params An optional array of query parameters
     * @return DatabaseException
     */
    protected function createException(PDOException $e, string $sql, ?array $params = []): DatabaseException
    {
        return new DatabaseException(
            $e->getMessage(),
            $e->getCode(),
            $sql,
            $params,
            [
                "errorCode" => $this->connection()->pdo()->errorCode(),
                "errorInfo" => $this->connection()->pdo()->errorInfo(),
            ],
            $e
        );
    }
}
