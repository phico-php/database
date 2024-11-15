<?php

declare(strict_types=1);

namespace Phico\Database\Schema;


class Foreign extends Index
{
    /**
     * Sets a standardised name for the index, used if the index name is not provided
     * e.g. table-name_columns_foreign_idx
     * @param array $columns
     * @return void
     */
    protected function setName(array $columns): void
    {
        $this->name = sprintf('%s_%s__foreign_idx', $this->table, join('_', $columns));
    }
    /**
     * Drops the index from the table
     * @return self
     */
    public function drop(): self
    {
        if ($this->dialect === 'sqlite') {
            throw new \BadMethodCallException('SQLite does not support dropping foreign keys, the table will have to be recreated');
        }

        $this->mode = 'drop';
        return $this;
    }
    /**
     * Returns the DDL statement for this index
     * @return string
     */
    public function toSql(): string
    {
        return match ($this->mode) {
            'create' => $this->getCreateStatement(),
            'drop' => $this->getDropStatement(),
        };
    }
    /**
     * Returns a create statement
     * @return string
     */
    protected function getCreateStatement(): string
    {
        // sprintf('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (%s);', $this->table, $this->name, $columns, $referencedTable, $referencedColumns);

        return sprintf(
            'CREATE%s INDEX %s ON %s (%s);',
            ($this->unique) ? ' UNIQUE' : '',
            $this->name,
            $this->table,
            join(', ', $this->columns)
        );
    }
    /**
     * Returns a drop statement
     * @return string
     */
    protected function getDropStatement(): string
    {
        return match ($this->dialect) {
            'mysql' => sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`;', $this->table, $this->name),
            'pgsql' => sprintf('ALTER TABLE "%s" DROP CONSTRAINT "%s" ;', $this->table, $this->name),
        };
    }
}
