<?php

namespace Phico\Database\Schema;

class Index
{
    private string $mode;
    private string $dialect;
    private string $table;
    private string $name;
    private object $rename;
    private bool $drop;
    private bool $unique;
    private array $columns = [];
    private array $foreign = [];


    public function __construct(
        string $dialect,
        string $table,
        array $columns,
        string $name = null
    ) {
        $this->mode = 'create';
        $this->dialect = $dialect;
        $this->table = $table;
        $this->name = $name ?? $this->makeName($columns);
        $this->columns = $columns;
        $this->drop = false;
        $this->unique = false;
    }

    public function drop(): self
    {
        if ($this->dialect === 'sqlite') {
            throw new \BadMethodCallException('SQLite does not support dropping foreign keys, the table will have to be recreated');
        }

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


    public function __toString(): string
    {
        switch ($this->mode) {
            case 'drop':
                return $this->makeDropSyntax();

            // case 'rename':
            //     return $this->makeRenameSyntax();

            case 'create':
            default:
                return $this->makeCreateSyntax();
        }
    }

    private function makeCreateSyntax(): string
    {
        $out = sprintf(
            'CREATE%s INDEX %s ON %s (%s);',
            ($this->unique) ? ' UNIQUE' : '',
            $this->name,
            $this->table,
            join(', ', $this->columns)
        );

        // sprintf('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s);', $this->table, $this->name, $columns, $referencedTable, $referencedColumns);


        return $out;
    }
    private function makeDropSyntax(): string
    {
        return match ($this->dialect) {
            'mysql', 'mariadb' => sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`;', $this->table, $this->name),
            'pgsql' => sprintf('ALTER TABLE "%s" DROP CONSTRAINT "%s" ;', $this->table, $this->name),
        };
    }

    private function makeName(array $columns): string
    {
        return sprintf('%s_%s__foindex', $this->name, join('_', $columns));
    }
}
