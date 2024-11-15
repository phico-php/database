<?php

declare(strict_types=1);

namespace Phico\Database\Schema;

use Phico\Database\DB;

/**
 * The base migration class which is extended by custom Migrations
 */
abstract class Migration
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
     * Code to make changes to the database
     * @return void
     */
    abstract public function up(): void;
    /**
     * Code that reverses the changes above
     * @return void
     */
    abstract public function down(): void;
}
