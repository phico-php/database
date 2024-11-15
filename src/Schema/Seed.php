<?php

declare(strict_types=1);

namespace Phico\Database\Schema;

use Phico\Database\DB;

/**
 * The base seed class which is extended by custom seeds
 */
abstract class Seed
{
    /**
     * The constructor requires a DB instance
     * @param \Phico\Database\DB $db
     */
    public function __construct(protected DB $db)
    {
        $this->db = $db;
    }
    /**
     * Code to insert or update data in the database
     * @return void
     */
    abstract public function seed(): void;
}
