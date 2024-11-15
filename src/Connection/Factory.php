<?php

declare(strict_types=1);

namespace Phico\Database\Connection;

use InvalidArgumentException;
use Phico\Database\DatabaseException;


/**
 * Creates and manages multiple database connections.
 *
 * @package Phico\Database
 * @category Database
 * @license BSD-3-Clause
 * @author indgy@phico-php.net
 */
class Factory
{
    /**
     * A structured config map containing the default connection and connection lists
     * @var array<string,mixed>
     */
    protected array $config;
    /**
     * A map of named connections
     * @var array<string,Connection>
     */
    protected array $connections;


    /**
     * The Connection Factory fetches any database config during construction.
     */
    public function __construct(?array $config = [])
    {
        // greb config from file if not provided
        $this->config = (empty($config)) ? config()->get('database', []) : $config;
        // ensure the config has a default
        if (!isset($this->config['use'])) {
            throw new InvalidArgumentException("Cannot get the default database connection, please add the 'use' key to your database config.");
        }
        // parse each connection config before use to throw errors early
        foreach ($this->config['connections'] as $k => $v) {
            $this->config['connections'][$k] = Config::fromArray($v);
        }
    }
    /**
     * Returns a Connection created from named config or a dsn string, the provided credentials will override any in config
     * @param ?string $name A named config to use, if empty the default connection is used
     * @return Connection
     */
    public function get(?string $name = null): Connection
    {
        // if name is not provided, then get the default connection name
        $name = $name ?? $this->config['use'];

        // check for existing connection in list
        if (!isset($this->connections[$name])) {
            // check config for connection
            if (!isset($this->config['connections'][$name])) {
                throw new InvalidArgumentException("Cannot get undefined Connection '$name', check your spelling or create an entry in your database connections list.");
            }
            // create connection and store in the list for future use
            $this->connections[$name] = new Connection($this->config['connections'][$name], $name);
        }

        // return named connection
        return $this->connections[$name];
    }
    /**
     * Creates a new connection from a DSN string
     * @param string $dsn
     * @param mixed $username
     * @param mixed $password
     * @param mixed $options
     * @throws DatabaseException
     * @return Connection
     */
    public function create(string $dsn, ?string $username = null, ?string $password = null, ?array $options = []): Connection
    {
        // check for existing connection in list
        if (!isset($this->connections[$dsn])) {
            // create connection and store in the list for future use
            $this->connections[$dsn] = new Connection(
                Config::fromDsn($dsn, $username, $password, $options),
                $dsn
            );
        }

        // return named connection
        return $this->connections[$dsn];
    }
}
