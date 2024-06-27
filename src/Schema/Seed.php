<?php

declare(strict_types=1);

namespace Phico\Database\Schema;

use Phico\Database\DB;

// base class for seeds
abstract class Seed
{
    public function __construct(protected DB $db)
    {
        $this->db = $db;
    }
    abstract public function seed(): void;
}
