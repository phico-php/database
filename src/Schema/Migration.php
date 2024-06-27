<?php

declare(strict_types=1);

namespace Phico\Database\Schema;

use Phico\Database\DB;

// base class for migrations
abstract class Migration
{
    public function __construct(protected DB $db)
    {
        $this->db = $db;
    }
    public function run(): void
    {
        $this->up();
    }
    public function rollback(): void
    {
        $this->down();
    }
    abstract public function up(): void;
    abstract public function down(): void;
}
