<?php

declare(strict_types=1);

namespace Phico\Database\Schema;

use Throwable;
use BadMethodCallException;
use InvalidArgumentException;


class Table
{
    /**
     * The database dialect to render
     * @var string
     */
    protected string $dialect;
    /**
     * The table name
     * @var string
     */
    protected string $name;
    /**
     * The table modification mode
     * create|alter|drop|rename|truncate
     * @var string
     */
    protected string $mode;
    /**
     * The exists or not exists statement
     * @var string
     */
    protected string $exists;
    /**
     * The list of columns to manipulate
     * @var array<int,Column>
     */
    protected array $columns = [];
    /**
     * The list of Indexes to manipulate
     * @var array<int,Index>
     */
    protected array $indices = [];
    /**
     * An array of table constraints
     * @var array<string,string>
     */
    protected array $constraints = [];


    public function __construct(string $dialect)
    {
        if (!in_array(strtolower($dialect), ['mysql', 'pgsql', 'sqlite'])) {
            throw new InvalidArgumentException(sprintf('Table cannot handle unsupported dialect %s', $dialect));
        }
        $this->dialect = strtolower($dialect);
    }
    /**
     * Allowed unknown method calls are passed to a new Column instance
     * @param string $name
     * @param array $args
     * @throws BadMethodCallException
     * @return Column
     */
    public function __call(string $name, array $args): Column
    {
        $disallow = [
            'drop',
            'autoIncrement',
            'comment',
            'default',
            'notNull',
            'nullable',
            'primary',
            'useCurrent',
        ];
        // disallowed methods cannot be the first method in the chain
        if (in_array($name, $disallow)) {
            throw new BadMethodCallException("Cannot call $name on table, call a column type first");
        }

        // column type is the first argument
        $type = array_shift($args);

        // @TODO handle size?

        // create the new column with name and type
        return $this->column($type)->$name(...$args);

    }
    /**
     * Renders the table DDL statement
     * @return string
     */
    public function toSql(): string
    {
        if ($this->mode === 'DROP') {
            return sprintf(
                'DROP TABLE %s%s;',
                ($this->exists) ? "$this->exists " : '',
                $this->quote($this->name)
            );
        }
        if ($this->mode === 'RENAME') {
            return sprintf(
                match ($this->dialect) {
                    'mysql' => 'RENAME TABLE %s%s TO %s;',
                    'pgsql', 'sqlite' => 'ALTER TABLE %s%s RENAME TO %s;',
                },
                ($this->exists) ? "$this->exists " : '',
                $this->quote($this->name),
                $this->quote($this->rename)
            );
        }
        if ($this->mode === 'TRUNCATE') {
            return match ($this->dialect) {
                'mysql', 'pgsql' => sprintf('TRUNCATE TABLE %s;', $this->quote($this->name)),
                'sqlite' => sprintf('DELETE FROM %s;', $this->quote($this->name)),
            };
        }
        if ($this->mode === 'ALTER') {
            $out = [];
            switch ($this->dialect) {
                case 'sqlite':
                    foreach ($this->columns as $name => $column) {
                        $out[] = match ($column->mode()) {
                            'alter' => throw new \LogicException('Altering columns in SQLite is not supported'),
                            'drop' => sprintf('ALTER TABLE %s DROP COLUMN %s', $this->quote($this->name), $column),
                            'rename' => sprintf('ALTER TABLE %s RENAME COLUMN %s', $this->quote($this->name), $column),
                            default => sprintf('ALTER TABLE %s ADD COLUMN %s', $this->quote($this->name), $column),
                        };
                    }
                    return join(";\n", $out);

                case 'mysql':
                case 'pgsql':
                    foreach ($this->columns as $name => $column) {
                        $out[] = match ($column->mode()) {
                            'alter' => sprintf('%s COLUMN %s', ($this->dialect == 'mysql') ? 'MODIFY' : 'ALTER', (string) $column),
                            'drop' => sprintf('DROP COLUMN %s', (string) $column),
                            'rename' => sprintf('RENAME COLUMN %s', $column),
                            default => (string) $column,
                        };
                    }
                    return sprintf('ALTER TABLE %s\n%s;', $this->quote($this->name), join(",\n", $out));
            }
        }

        $constraints = [];
        $columns = [];
        foreach ($this->columns as $name => $column) {
            // check for primary column
            if ($column->isPrimary()) {
                $this->primary($name);
            }
            $columns[] = $column->toSql();
        }

        $indices = [];
        foreach ($this->indices as $name => $index) {
            $indices[] = $index->toSql();
        }

        return sprintf(
            "%s TABLE %s %s (\n\t%s\n\t);\n%s\n",
            $this->mode,
            $this->exists ?? '',
            $this->quote($this->name),
            join(",\n\t", $columns + $this->constraints),
            join("\n", $indices)
        );
    }
    /**
     * Adds a new column to the table.
     * This method 'abuses' the try catch finally logic to enable Column method
     * chaining and also attaching the completed chain to the columns array.
     * @param string $name The name of the column
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     * @return Column
     */
    public function column(string $name): Column
    {
        try {

            if (!in_array($this->mode, ['ALTER', 'CREATE'])) {
                throw new BadMethodCallException(sprintf("Cannot handle columns during a %s operation", $this->mode));
            }

            if (array_key_exists($name, $this->columns)) {
                throw new InvalidArgumentException("Cannot create duplicate column name '$name'");
            }

            $column = new Column($this->dialect, $name);
            return $column;

        } catch (Throwable $th) {

            throw $th;

        } finally {

            // not the best practice, but a great feature in this case ;)
            if (isset($column)) { // column might not be set on error
                $this->columns[$name] = $column;
            }

        }

    }
    /**
     * Adds an index to the table.
     * This method 'abuses' the try catch finally logic to enable Index method
     * chaining and also attaching the completed chain to the table.
     * @param array|string $columns
     * @param string $name
     * @throws InvalidArgumentException
     * @return Index
     */
    public function index(array|string $columns, string $name = null): Index
    {
        try {

            if (is_string($columns)) {
                $columns = [$columns];
            }

            sort($columns);

            if (is_null($name)) {
                $name = sprintf('%s_index_%s', $this->name, join('_', $columns));
            }

            if (array_key_exists($name, $this->indices)) {
                throw new InvalidArgumentException("Cannot create duplicate index name '$name'");
            }

            $index = new Index($this->dialect, $this->name, $columns, $name);
            return $index;

        } catch (Throwable $th) {

            throw $th;

        } finally {

            // not the best practice, but a great feature in this case ;)
            if (isset($index)) { // index might not be set on error
                $this->indices[$name] = $index;
            }

        }
    }
    /**
     * Adds a foreign index to the table.
     * This method 'abuses' the try catch finally logic to enable Foreign method
     * chaining and also attaching the completed chain to the table.
     * @param array|string $columns
     * @param string $name
     * @throws InvalidArgumentException
     * @return Foreign
     */
    public function foreign(array|string $columns, string $name = null): Foreign
    {
        try {

            if (is_string($columns)) {
                $columns = [$columns];
            }

            sort($columns);

            if (is_null($name)) {
                $name = sprintf('%s_index_%s', $this->name, join('_', $columns));
            }

            if (array_key_exists($name, $this->indices)) {
                throw new InvalidArgumentException("Cannot create duplicate index name '$name'");
            }

            $index = new Foreign($this->dialect, $this->name, $columns, $name);
            return $index;

        } catch (Throwable $th) {

            throw $th;

        } finally {

            // not the best practice, but a great feature in this case ;)
            if (isset($index)) { // index might not be set on error
                $this->indices[$name] = $index;
            }

        }
    }
    /**
     * Convenience method for unique indexes, simply calls the unique() method after the index is created.
     * @param array|string $columns
     * @param string $name
     * @return Index
     */
    public function unique(array|string $columns, string $name = null): Index
    {
        return $this->index($columns, $name)->unique();
    }

    /**
     * Switches DDL statement to alter mode
     * @param string $name The name of the table to operate on
     * @return self
     */
    public function alter(string $name): self
    {
        $this->mode = 'ALTER';
        $this->name = $name;
        $this->exists = $this->exists ?? '';

        return $this;
    }
    /**
     * Switches DDL statement to create mode
     * @param string $name The name of the table to operate on
     * @return self
     */
    public function create(string $name): self
    {
        $this->mode = 'CREATE';
        $this->name = $name;
        $this->exists = $this->exists ?? '';

        return $this;
    }
    /**
     * Switches DDL statement to drop mode
     * @param string $name The name of the table to operate on
     * @return self
     */
    public function drop(string $name): self
    {
        $this->mode = 'DROP';
        $this->name = $name;
        $this->exists = $this->exists ?? '';

        return $this;
    }
    /**
     * Switches DDL statement to drop mode adding the if exists constraint
     * @param string $name The name of the table to operate on
     * @return self
     */
    public function dropIfExists(string $name): self
    {
        $this->mode = 'DROP';
        $this->name = $name;
        $this->exists = 'IF EXISTS';

        return $this;
    }
    /**
     * Switches DDL statement to rename mode
     * @param string $old The current name of the table
     * @param string $new The new name of the table
     * @return self
     */
    public function rename(string $old, string $new): self
    {
        $this->mode = 'RENAME';
        $this->name = $old;
        $this->rename = $new;
        $this->exists = $this->exists ?? '';

        return $this;
    }
    /**
     * Switches DDL statement to rename mode adding the if exists constraint
     * @param string $old The current name of the table
     * @param string $new The new name of the table
     * @return self
     */
    public function renameIfExists(string $old, string $new): self
    {
        $this->mode = 'RENAME';
        $this->name = $old;
        $this->rename = $new;
        $this->exists = 'IF EXISTS';

        return $this;
    }
    /**
     * Switches DDL statement to truncate mode
     * @return self
     */
    public function truncate(string $name): self
    {
        $this->mode = 'TRUNCATE';
        $this->name = $name;

        return $this;
    }

    // convenience column helpers

    /**
     * Adds deleted_at timestamp and deleted_by integer columns
     * @return self
     */
    public function softDelete(): self
    {
        $this->columns['deleted_at'] = (new Column($this->dialect, 'deleted_at'))
            ->timestamp()
            ->nullable();
        $this->columns['deleted_by'] = (new Column($this->dialect, 'deleted_by'))
            ->integer()
            ->nullable();

        return $this;
    }
    /**
     * Adds created_at and updated_at timestamp columns
     * @return self
     */
    public function timestamps(): self
    {
        $this->columns['created_at'] = (new Column($this->dialect, 'created_at'))
            ->timestamp()
            ->nullable();
        $this->columns['updated_at'] = (new Column($this->dialect, 'updated_at'))
            ->timestamp()
            ->nullable();

        return $this;
    }
    /**
     * Adds created_by and updated_by timestamp columns
     * @return self
     */
    public function userstamps(): self
    {
        $this->columns['created_by'] = (new Column($this->dialect, 'created_by'))
            ->integer()
            ->nullable();
        $this->columns['updated_by'] = (new Column($this->dialect, 'updated_by'))
            ->integer()
            ->nullable();

        return $this;
    }

    // constraints

    /**
     * Sets the charset constraint value.
     * @param string $charset The table charset
     * @return self
     */
    public function charset(string $charset): self
    {
        $this->constraints['charset'] = $charset;
        return $this;
    }
    /**
     * Sets the collation constraint value.
     * @param string $collation The table collation
     * @return self
     */
    public function collation(string $collation): self
    {
        $this->constraints['collation'] = $collation;
        return $this;
    }
    /**
     * Sets the table engine value
     * @param string $engine The table engine to use
     * @return self
     */
    public function engine(string $engine): self
    {
        $this->constraints['engine'] = $engine;
        return $this;
    }
    /**
     * Sets the if exists constraint.
     * @return self
     */
    public function ifExists(): self
    {
        $this->exists = 'IF EXISTS';

        return $this;
    }
    /**
     * Sets the if not exists constraint.
     * @return self
     */
    public function ifNotExists(): self
    {
        $this->exists = 'IF NOT EXISTS';

        return $this;
    }
    /**
     * Sets the table primary key column name
     * @param array|string $name The name of the primary column
     * @return self
     */
    public function primary(array|string $name): self
    {
        $this->constraints['primary'] = match ($this->dialect) {
            'mysql', 'sqlite' => sprintf('PRIMARY KEY (%s)', $this->quote($name)),
            'pgsql' => '',
        };
        return $this;
    }
    /**
     * Sets the strict constraint
     * @return self
     */
    public function strict(): self
    {
        $this->constraints['strict'] = 'strict';
        return $this;
    }
    /**
     * Sets the 'without rowid' constraint
     * @return self
     */
    public function withoutRowId(): self
    {
        $this->constraints['without rowid'] = 'without rowid';
        return $this;
    }

    /**
     * Returns a dialect appropriate quoted string
     * (This code is repeated in Column.php)
     * @param string $str The string to quote
     * @return string
     */
    protected function quote(string $str): string
    {
        return match ($this->dialect) {
            'mysql' => sprintf('`%s`', $str),
            'pgsql', 'sqlite' => sprintf('"%s"', $str),
        };
    }
}
