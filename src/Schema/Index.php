<?php

namespace Phico\Database\Schema;

class Index
{
    private string $mode;
    private string $driver;
    private string $table;
    private string $name;
    private object $rename;
    private bool $drop;
    private bool $unique;
    private array $columns = [];


    public function __construct(
        string $driver,
        string $table,
        array $columns,
        string $name = null
    ) {
        $this->mode = 'create';
        $this->driver = $driver;
        $this->table = $table;
        $this->name = $name;
        $this->columns = $columns;
        $this->drop = false;
        $this->unique = false;
    }

    public function drop(): self
    {
        $this->mode = 'drop';
        return $this;
    }
    public function rename(string $old, string $new): self
    {
        $this->mode = 'rename';
        $this->rename = (object) [
            'old' => $old,
            'new' => $new
        ];
        return $this;
    }
    public function unique(): self
    {
        $this->unique = true;
        return $this;
    }


    public function __toString(): string
    {
        if ($this->mode == 'drop') {
            return match ($this->driver) {
                'mysql', 'mariadb' => sprintf('DROP INDEX %s ON %s;', $this->name, $this->table),
                'pgsql', 'sqlite' => sprintf('DROP INDEX %s;', $this->name),
            };
        }

        if ($this->mode == 'rename') {
            return match ($this->driver) {
                'mysql', 'mariadb' => sprintf('ALTER TABLE %s RENAME INDEX %s TO %s;', $this->table, $this->rename->old, $this->rename->new),
                'pgsql' => sprintf('ALTER INDEX %s RENAME TO %s;', $this->rename->old, $this->rename->new),
                'sqlite' => sprintf('DROP INDEX %s; CREATE INDEX %s ON %s (%s);', $this->rename->old, $this->rename->new, $this->table, join(', ', $this->columns)),
            };
        }

        return sprintf('CREATE%s INDEX %s ON %s (%s);', ($this->unique === true) ? ' UNIQUE' : '', $this->name, $this->table, join(', ', $this->columns));

    }
}
