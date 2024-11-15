<?php

declare(strict_types=1);

namespace Phico\Database\Connection;

use InvalidArgumentException;

/**
 * Represents a sane database config, created from a config array or DSN notation.
 *
 * @package Phico\Database
 * @category Database
 * @license BSD-3-Clause
 * @author indgy@phico-php.net
 */
class Config
{
    protected string $driver;
    protected string $database;
    protected ?string $host;
    protected ?int $port;
    protected ?string $socket;
    protected ?string $charset;
    protected ?string $username;
    protected ?string $password;
    protected ?array $options;

    /**
     * Returns a Config instance from a config map
     * @param array<string,mixed> $config
     * @throws InvalidArgumentException
     * @return self
     */
    public static function fromArray(array $config): self
    {
        // ensure we have the most basic required fields
        if (!isset($config["driver"])) {
            throw new InvalidArgumentException(
                "Cannot create Config from array as it is missing the driver name"
            );
        }
        if (!isset($config["database"])) {
            throw new InvalidArgumentException(
                "Cannot create Config from array as it is missing the database name"
            );
        }

        // provide config values or null
        return new Config(
            driver: $config["driver"],
            database: $config["database"],
            host: $config["host"] ?? null,
            port: !empty($config["port"]) ? intval($config["port"]) : null,
            socket: $config["socket"] ?? $config["unix_socket"] ?? null,
            charset: $config["charset"] ?? null,
            username: $config["username"] ?? null,
            password: $config["password"] ?? null,
            options: $config["options"] ?? null
        );
    }
    /**
     * Returns a Config instance from a DSN string, credentials and options.
     * This methods creates an array from the arguments and calls the fromArray() method above
     * @param string $dsn The DSN connection string
     * @param ?string $username An optional user to connect as
     * @param ?string $password An optional password for the user
     * @param ?array $options An optional array of PDO options
     * @throws InvalidArgumentException
     * @return self
     */
    public static function fromDsn(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        ?array $options = []
    ): Config {
        // shortcut for simpler sqlite syntax
        if (str_starts_with(strtolower($dsn), "sqlite:")) {
            // memory and file databases have different formats
            $database = strtolower($dsn) === "sqlite::memory:" ? ":memory:" : substr($dsn, 7);
            return new Config(
                driver: "sqlite",
                database: $database,
                username: $username,
                password: $password,
                options: $options
            );
        }

        // parse dsn, split into 'driver : rest of string'
        $parts = explode(":", $dsn);
        if (count($parts) < 2) {
            throw new InvalidArgumentException(sprintf("Cannot create Config from invalid dsn '%s'", $dsn));
        }

        // init empty config
        $config = [
            "driver" => null,
            "database" => null,
            "host" => null,
            "port" => null,
            "socket" => null,
            "charset" => null,
            "username" => $username,
            "password" => $password,
            "options" => $options,
        ];

        // set the driver from the first part
        $config["driver"] = $parts[0];
        // set the rest of the values from the second part
        foreach (explode(";", $parts[1]) as $param) {
            list($k, $v) = explode("=", $param);
            $k = strtolower($k);
            if (array_key_exists($k, $config)) {
                $config[$k] = $v;
            }
            if ($k === "dbname") {
                $config["database"] = $v;
            }
            if ($k === "unix_socket") {
                $config["socket"] = $v;
            }
        }

        return self::fromArray($config);
    }

    /**
     * Pass in all config values through the constructor
     * @param string $driver The database driver, only mysql, pgsql, sqlite supported
     * @param string $database The database name
     * @param ?string $host An optional server hostname
     * @param ?int $port An optional server port
     * @param ?string $socket An optional socket path
     * @param ?string $charset An optional character set identifier
     * @param ?string $username An optional user to connect as
     * @param ?string $password An optional password for the user
     * @param ?array $options An optional array of PDO options
     */
    public function __construct(
        string $driver,
        string $database,
        ?string $host = null,
        ?int $port = null,
        ?string $socket = null,
        ?string $charset = null,
        ?string $username = null,
        ?string $password = null,
        ?array $options = []
    ) {
        $this->setDriver($driver);
        $this->setDatabase($database);
        $this->setHost($host);
        $this->setPort($port);
        $this->setSocket($socket);
        $this->setCharset($charset);
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setOptions($options);

        // sanity check
        $this->check();
    }

    /**
     * Sets the driver name
     * @param string $driver
     * @return void
     */
    protected function setDriver(string $driver): void
    {
        $this->driver = strtolower($driver);
    }
    /**
     * Sets the database name
     * @param string $database
     * @return void
     */
    protected function setDatabase(string $database): void
    {
        $this->database = $database;
    }
    /**
     * Sets the server host name
     * @param string $host
     * @return void
     */
    protected function setHost(string $host = null): void
    {
        $this->host = $host;
    }
    /**
     * Sets the port number, converting from string to int
     * @param int|string $port
     * @return void
     */
    protected function setPort(int|string $port = null): void
    {
        if (!empty($port)) {
            $port = intval($port);
        } elseif (empty($port) and isset($this->driver)) {
            $port = match ($this->driver) {
                "mysql" => 3306,
                "pgsql" => 5432,
                default => null,
            };
        }

        $this->port = $port;
    }
    /**
     * Sets the socket path
     * @param string $socket
     * @return void
     */
    protected function setSocket(string $socket = null): void
    {
        $this->socket = $socket;
    }
    /**
     * Sets the connection charset
     * @param string $charset
     * @return void
     */
    protected function setCharset(string $charset = null): void
    {
        $this->charset = $charset;
    }
    /**
     * Sets the username to connect as
     * @param string $username
     * @return void
     */
    protected function setUsername(string $username = null): void
    {
        $this->username = $username;
    }
    /**
     * Sets the password to connect with
     * @param string $password
     * @return void
     */
    protected function setPassword(string $password = null): void
    {
        $this->password = $password;
    }
    /**
     * Sets the PDO options array
     * @param array $options
     * @return void
     */
    protected function setOptions(array $options = null): void
    {
        // @TODO check these are valid PDO consts?
        $this->options = $options;
    }

    /**
     * Returns the dsn connection string
     * @return string
     */
    public function getDsn(): string
    {
        // shortcut for simpler sqlite syntax
        if ($this->driver === "sqlite") {
            return match ($this->database) {
                "memory", ":memory:" => "sqlite::memory:",
                default => sprintf("sqlite:%s", $this->database),
            };
        }

        // set the driver first
        $dsn = "{$this->driver}:";
        // choose socket or host and port
        if (!empty($this->socket)) {
            $dsn .= "unix_socket={$this->socket};";
        } elseif (!empty($this->host)) {
            $dsn .= "host={$this->host};port={$this->port};";
        }
        // provide database name
        $dsn .= "dbname={$this->database};";
        // provide charset if specified
        if (!empty($this->charset)) {
            $dsn .= "charset={$this->charset};";
        }

        return rtrim($dsn, ";");
    }
    /**
     * Gets the driver name
     * @return string|null
     */
    public function getDriver(): ?string
    {
        return $this->driver;
    }
    /**
     * Gets the database name
     * @return string|null
     */
    public function getDatabase(): ?string
    {
        return $this->database;
    }
    /**
     * Gets the server host name
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }
    /**
     * Gets the port number
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port;
    }
    /**
     * Gets the socket path
     * @return string|null
     */
    public function getSocket(): ?string
    {
        return $this->socket;
    }
    /**
     * Gets the connection charset
     * @return string|null
     */
    public function getCharset(): ?string
    {
        return $this->charset;
    }
    /**
     * Gets the username
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }
    /**
     * Gets the password
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }
    /**
     * Gets the PDO options array
     * @return array|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * Returns the config as an array
     * @return array
     */
    public function toArray(): array
    {
        return [
            "driver" => $this->driver,
            "database" => $this->database,
            "host" => $this->host,
            "port" => $this->port,
            "socket" => $this->socket,
            "charset" => $this->charset,
            "username" => $this->username,
            "password" => $this->password,
            "options" => $this->options,
        ];
    }

    /**
     * Sanity check the properties for correctness.
     * All checks are handled in this method which is called by the constructor.
     * @throws InvalidArgumentException
     * @return void
     */
    protected function check(): void
    {
        // check for unsupported driver
        if (!in_array((string) $this->driver, ["mysql", "pgsql", "sqlite"])) {
            throw new InvalidArgumentException(sprintf("Cannot use unsupported driver %s", $this->driver));
        }
        if ($this->driver === "sqlite") {
            // check we don't have socket AND hostname
            if (!is_null($this->socket) or !is_null($this->host) or !is_null($this->port)) {
                throw new InvalidArgumentException(sprintf("SQLite does not require host and port or socket details"));
            }
            // SQLite silently ignores this, lets inform the dev
            if (!is_null($this->charset)) {
                throw new InvalidArgumentException(sprintf("SQLite does not require a charset, it is always UTF-8"));
            }
            // SQLite does not require a username or password
            if (!is_null($this->username) or !is_null($this->password)) {
                throw new InvalidArgumentException(sprintf("SQLite does not require authentication credentials"));
            }
        }

        if (in_array($this->driver, ["mysql", "pgsql"])) {
            // check that we have socket OR hostname
            if (is_null($this->socket) and is_null($this->host)) {
                throw new InvalidArgumentException(
                    sprintf("Missing host and socket for connection, please provide a hostname or socket to connect to")
                );
            }
            // check we don't have socket AND hostname
            if (!is_null($this->socket) and !is_null($this->host)) {
                throw new InvalidArgumentException(
                    sprintf("Passed host and socket for connection, use one or the other not both")
                );
            }
            // check port is an integer if hostname is provided
            if (!is_null($this->host) and !is_null($this->port) and !is_numeric($this->port)) {
                throw new InvalidArgumentException(sprintf("Port must be a numeric value", gettype($this->port)));
            }
        }
    }
}
