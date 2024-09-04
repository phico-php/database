<?php

namespace Phico\Database\Schema;

class Index
{
    private string $mode;
    private string $driver;
    private string $table;
    private string $name;
    private ?object $rename = null;
    private bool $drop;
    private bool $unique;
    private array $columns = [];
    private ?array $foreignKey = null;

    public function __construct(
        string $driver,
        string $table,
        array $columns,
        string $name = null
    ) {
        $this->mode = 'create';
        $this->driver = $driver;
        $this->table = $table;
        $this->name = $name ?? $this->generateIndexName($columns);
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

    public function addForeignKey(array $foreignKey): self
    {
        $this->foreignKey = $foreignKey;
        return $this;
    }

    public function __toString(): string
    {
        switch ($this->mode) {
            case 'drop':
                return $this->generateDropIndexQuery();
            case 'rename':
                return $this->generateRenameIndexQuery();
            case 'create':
            default:
                return $this->generateCreateIndexQuery();
        }
    }

    private function generateIndexName(array $columns): string
    {
        return $this->table . '_' . implode('_', $columns) . '_idx';
    }

    private function generateDropIndexQuery(): string
    {
        return match ($this->driver) {
            'mysql', 'mariadb' => sprintf('DROP INDEX %s ON %s;', $this->name, $this->table),
            'pgsql', 'sqlite' => sprintf('DROP INDEX %s;', $this->name),
            default => throw new \Exception('Unsupported driver: ' . $this->driver),
        };
    }

    private function generateRenameIndexQuery(): string
    {
        return match ($this->driver) {
            'mysql', 'mariadb' => sprintf('ALTER TABLE %s RENAME INDEX %s TO %s;', $this->table, $this->rename->old, $this->rename->new),
            'pgsql' => sprintf('ALTER INDEX %s RENAME TO %s;', $this->rename->old, $this->rename->new),
            'sqlite' => sprintf('DROP INDEX %s; CREATE INDEX %s ON %s (%s);', $this->rename->old, $this->rename->new, $this->table, join(', ', $this->columns)),
            default => throw new \Exception('Unsupported driver: ' . $this->driver),
        };
    }

    private function generateCreateIndexQuery(): string
    {
        $unique = $this->unique ? ' UNIQUE' : '';
        $indexQuery = sprintf('CREATE%s INDEX %s ON %s (%s);', $unique, $this->name, $this->table, join(', ', $this->columns));

        if ($this->foreignKey) {
            $indexQuery .= ' ' . $this->generateForeignKeyQuery();
        }

        return $indexQuery;
    }

    private function generateForeignKeyQuery(): string
    {
        if (!$this->foreignKey) {
            return '';
        }

        $columns = join(', ', $this->foreignKey['columns']);
        $referencedTable = $this->foreignKey['referenced_table'];
        $referencedColumns = join(', ', $this->foreignKey['referenced_columns']);

        return match ($this->driver) {
            'mysql', 'mariadb', 'pgsql' => sprintf('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s);', $this->table, $this->name, $columns, $referencedTable, $referencedColumns),
            'sqlite' => sprintf('ALTER TABLE %s ADD FOREIGN KEY (%s) REFERENCES %s (%s);', $this->table, $columns, $referencedTable, $referencedColumns),
            default => throw new \Exception('Unsupported driver: ' . $this->driver),
        };
    }
}
