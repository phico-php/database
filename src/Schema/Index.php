<?php

declare(strict_types=1);

namespace Phico\Database\Schema;


class Index
{
    /**
     * The modification mode
     * create|drop|rename
     * @var string
     */
    protected string $mode;
    /**
     * The DDL dialect
     * @var string
     */
    protected string $dialect;
    /**
     * The table name
     * @var string
     */
    protected string $table;
    /**
     * The index name
     * @var string
     */
    protected string $name;
    /**
     * The new name for the index
     * @var string
     */
    protected string $rename;
    /**
     * Flag to toggle unique constraint
     * @var bool
     */
    protected bool $unique;
    /**
     * The list of columns to be indexed
     * @var array<int,string>
     */
    protected array $columns = [];


    /**
     * The constructor requires the driver, table name, columns to be indexed and an optional index name
     * @param string $dialect The DDL dialect to return
     * @param string $table The table name
     * @param array $columns The columns to index
     * @param string $name An optional name for the index, will be auto generated if left blank
     */
    public function __construct(
        string $dialect,
        string $table,
        array $columns,
        string $name = null
    ) {
        $this->mode = 'create';
        $this->dialect = $dialect;
        $this->table = $table;
        $this->columns = $columns;
        $this->unique = false;

        // auto generate the name if left blank
        if (is_null($name)) {
            $this->setName($columns);
        } else {
            $this->name = $name;
        }
    }
    /**
     * Sets a standardised name for the index, used if the index name is not provided
     * e.g. col-name[_unique]_columns_idx
     * @param array $columns
     * @return void
     */
    protected function setName(array $columns): void
    {
        $this->name = sprintf(
            '%s_%s%s_idx',
            $this->table,
            ($this->unique) ? '_unique ' : '',
            join('_', $columns)
        );
    }
    /**
     * Returns the DDL statement for this index.
     * @return string
     */
    public function toSql(): string
    {
        return match ($this->mode) {
            'create' => $this->getCreateStatement(),
            'drop' => $this->getDropStatement(),
            'rename' => $this->getRenameStatement(),
        };
    }
    /**
     * Drops the index from the table.
     * @return self
     */
    public function drop(): self
    {
        $this->mode = 'drop';
        return $this;
    }
    /**
     * Renames the index from old to new.
     * @param string $old The current name
     * @param string $new The new name
     * @return self
     */
    public function rename(string $old, string $new): self
    {
        $this->mode = 'rename';
        $this->name = $old;
        $this->rename = $new;
        return $this;
    }
    /**
     * Sets the unique flag.
     * @return self
     */
    public function unique(): self
    {
        $this->unique = true;
        return $this;
    }
    /**
     * Returns a create statement.
     * @return string
     */
    protected function getCreateStatement(): string
    {
        return sprintf(
            'CREATE%s INDEX %s ON %s (%s);',
            ($this->unique) ? ' UNIQUE' : '',
            $this->name,
            $this->table,
            join(', ', $this->columns)
        );
    }
    /**
     * Returns a drop statement.
     * @return string
     */
    protected function getDropStatement(): string
    {
        return match ($this->dialect) {
            'mysql' => sprintf('DROP INDEX %s ON %s;', $this->name, $this->table),
            'pgsql', 'sqlite' => sprintf('DROP INDEX %s;', $this->name),
        };
    }
    /**
     * Returns a rename statement.
     * @return string
     */
    protected function getRenameStatement(): string
    {
        return match ($this->dialect) {
            'mysql' => sprintf('ALTER TABLE %s RENAME INDEX %s TO %s;', $this->table, $this->name, $this->rename),
            'pgsql' => sprintf('ALTER INDEX %s RENAME TO %s;', $this->name, $this->rename),
            'sqlite' => sprintf('DROP INDEX %s; CREATE INDEX %s ON %s (%s);', $this->name, $this->rename, $this->table, join(', ', $this->columns)),
        };
    }
}
