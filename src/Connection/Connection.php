<?php

declare(strict_types=1);

namespace Phico\Database\Connection;

use BadMethodCallException;
use InvalidArgumentException;
use PDO;
use PDOException;
use Throwable;
use Phico\Database\DatabaseException;


/**
 * Represents a single PDO connection with Config.
 *
 * @package Phico\Database
 * @category Database
 * @license BSD-3-Clause
 * @author indgy@phico-php.net
 */
class Connection
{
    /**
     * The PDO instance
     * @var PDO
     */
    protected PDO $pdo;
    /**
     * The Config instance
     * @var Config
     */
    protected Config $config;
    /**
     * The connection name string
     * @var string
     */
    protected string $name;
    /**
     * The current transaction level
     * @var int
     */
    protected int $tx_level;
    /**
     * The cached PDO attributes
     * @var array<int,mixed>
     */
    protected array $attributes;


    /**
     * Connection requires a Config instance, a name can be provided for reference.
     * If name is not provided the DSN is used instead.
     * @param Config $config The connection configuration
     * @param ?string $name An optional name for future reference
     */
    public function __construct(
        Config $config,
        ?string $name = null,
    ) {
        $this->tx_level = 0;
        $this->config = $config;
        $this->name = $name ?? $config->getDsn();
    }
    /**
     * Returns the connection name
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    /**
     * Returns true if this connection is in a transaction.
     * Required by the Database class when switching connections.
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->tx_level > 0;
    }
    /**
     * Returns the specified PDO connection attribute value.
     * Attribute values are cached for future lookups.
     * @param int $constant The PDO constant for the attribute
     * @return mixed
     */
    public function attr(int $constant): mixed
    {
        if (!isset($this->attributes[$constant])) {
            try {
                $this->attributes[$constant] = $this->pdo()->getAttribute($constant);
            } catch (Throwable $th) {
                $this->attributes[$constant] = $th->getMessage();
            }
        }

        return $this->attributes[$constant];
    }
    /**
     * Returns all available PDO connection attributes.
     * Attribute valuesa are cached for future lookups.
     * @return array
     */
    public function attrs(): array
    {
        if (isset($this->attributes) and !empty($this->attributes)) {
            return $this->attributes;
        }

        $this->attributes = [];
        $attrs = [
            PDO::ATTR_AUTOCOMMIT,
            PDO::ATTR_CASE,
            PDO::ATTR_CLIENT_VERSION,
            PDO::ATTR_CONNECTION_STATUS,
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::ATTR_DEFAULT_STR_PARAM,
            PDO::ATTR_DRIVER_NAME,
            PDO::ATTR_EMULATE_PREPARES,
            PDO::ATTR_ERRMODE,
            PDO::ATTR_PERSISTENT,
            PDO::ATTR_PREFETCH,
            PDO::ATTR_SERVER_INFO,
            PDO::ATTR_SERVER_VERSION,
            PDO::ATTR_STRINGIFY_FETCHES,
            PDO::ATTR_TIMEOUT,
        ];
        foreach ($attrs as $attr) {
            // call attr which sets and returns the PDO attribute
            $this->attr($attr);
        }

        return $this->attributes;
    }
    /**
     * Creates a PDO connection to the database
     * @throws DatabaseException On failure
     * @return void
     */
    public function connect(): void
    {
        try {
            $this->pdo = new PDO(
                $this->config->getDsn(),
                $this->config->getUsername(),
                $this->config->getPassword(),
                $this->config->getOptions()
            );
        } catch (PDOException $e) {

            var_dump($e->getCode());

            throw new DatabaseException(
                $e->getMessage() . $this->config->getDsn(),
                10010,
                '',
                [
                    "dsn" => $this->config->getDsn(),
                    "username" => $this->config->getUsername(),
                    "password" => $this->config->getPassword(),
                    "options" => $this->config->getOptions(),
                ],
                $this->config->toArray(),
                $e
            );
        }
    }
    /**
     * Returns a copy of the Config instance.
     * @return Config
     */
    public function config(): Config
    {
        return clone $this->config;
    }
    /**
     * Returns a lazy loaded PDO instance.
     * @return PDO
     */
    public function pdo(): PDO
    {
        if (!isset($this->pdo)) {
            $this->connect();
        }
        return $this->pdo;
    }
    /**
     * Begin a new transaction (nested transactions are ignored)
     * @return void
     */
    public function begin(): void
    {
        $this->tx_level++;
        if ($this->tx_level == 1) {
            $this->pdo()->beginTransaction();
        }
    }
    /**
     * Commit the active transaction (nested transactions are ignored)
     * @return void
     */
    public function commit(): void
    {
        if ($this->tx_level == 1) {
            $this->pdo()->commit();
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
            $this->pdo()->rollback();
        }
        $this->tx_level--;
    }
    /**
     * Create a savepoint to rollback to
     * @param string $name The name of the savepoint to create
     * @return void
     */
    public function savepoint(string $name): void
    {
        $this->assertName($name);
        $this->assertNotInTransaction(
            sprintf(
                "Cannot use savepoints outside transactions, please start a transaction before calling savepoint %s",
                $name
            )
        );

        $this->pdo()->exec("SAVEPOINT $name");
    }
    /**
     * Rollback to a named savepoint
     * @param string $name The name of the savepoint to rollback to
     * @return bool Returns true on success, false on failure
     */
    public function rollbackTo(string $name): bool
    {
        $this->assertName($name);
        $this->assertNotInTransaction(
            sprintf(
                "Cannot use savepoints outside transactions, please start a transaction before calling rollback to %s",
                $name
            )
        );

        try {
            $this->pdo()->exec("ROLLBACK TO SAVEPOINT $name");
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    /**
     * Ensure savepoint names are valid
     * @param string $name
     * @return void
     * @throws InvalidArgumentException
     */
    protected function assertName(string $name): void
    {
        if (strlen($name) == 0) {
            throw new InvalidArgumentException("Name cannot be empty");
        }
        if (strlen($name) > 255) {
            throw new InvalidArgumentException("Name must be less than 255 characters");
        }
        if (preg_match("/[^a-z0-9_-]/i", $name)) {
            throw new InvalidArgumentException(
                "Name can only contain ascii letters a-z, numbers 0-9, dash - and underscore _"
            );
        }
    }
    /**
     * Ensure transactions are not in progress
     * @param ?string $msg The error message to throw
     * @return void
     * @throws InvalidArgumentException
     */
    protected function assertNotInTransaction(?string $msg = "Cannot switch connections during a transaction"): void
    {
        if ($this->inTransaction()) {
            throw new BadMethodCallException($msg);
        }
    }
}
