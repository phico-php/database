<?php

namespace Phico\Database\Schema;

use BadMethodCallException;
use InvalidArgumentException;

class Table
{
    private string $name;
    private string $rename;
    private string $dialect;
    private string $mode; // alter | create
    private string $exists; // if (not) exists
    private array $columns = [];
    private array $indices = [];
    private array $constraints = [];


    public function __construct(string $dialect)
    {
        if (!in_array(strtolower($dialect), ['mysql', 'mariadb', 'pgsql', 'sqlite'])) {
            throw new InvalidArgumentException(sprintf('Table cannot handle unsupported dialect %s', $dialect));
        }
        $this->dialect = strtolower($dialect);
    }
    public function __call(string $name, array $args): mixed
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

        if (in_array($name, $disallow)) {
            throw new BadMethodCallException("Cannot call $name on table, call a column type first");
        }

        $type = array_shift($args);
        return $this->column($type)->$name(...$args);

    }
    public function __toString(): string
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
                    'mysql', 'mariadb' => 'RENAME TABLE %s%s TO %s;',
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

        $cons = [];
        $cols = [];
        foreach ($this->columns as $name => $column) {
            // check for primary column
            if ($column->isPrimary()) {
                $cons[] = sprintf('PRIMARY KEY(%s)', $name);
            }
            $cols[] = (string) $column;
        }

        $inds = [];
        foreach ($this->indices as $name => $index) {
            $inds[] = (string) $index;
        }

        return sprintf(
            "%s TABLE %s %s (\n\t%s\n\t);\n%s\n",
            $this->mode,
            $this->exists ?? '',
            $this->quote($this->name),
            join(",\n\t", $cols + $cons),
            join("\n", $inds)
        );
    }

    public function toSql(): string
    {
        return $this->__toString();
    }

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

        } catch (\Throwable $th) {

            throw $th;

        } finally {

            // not the best practise, but a great feature in this case ;)
            if (isset($column)) { // column might not be set on error
                $this->columns[$name] = $column;
            }

        }

    }
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

        } catch (\Throwable $th) {

            throw $th;

        } finally {

            // not the best practise, but a great feature in this case ;)
            // @phpstan-ignore variable.undefined
            $this->indices[$name] = $index;

        }
    }
    public function foreign(array|string $columns, string $name = null): Index
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

        } catch (\Throwable $th) {

            throw $th;

        } finally {

            // not the best practise, but a great feature in this case ;)
            // @phpstan-ignore variable.undefined
            $this->indices[$name] = $index;

        }
    }
    public function unique(array|string $columns, string $name = null): Index
    {
        return $this->index($columns, $name)->unique();
    }

    public function alter(string $name): self
    {
        $this->mode = 'ALTER';
        $this->name = $name;
        $this->exists = $this->exists ?? '';
        return $this;
    }
    public function create(string $name): self
    {
        $this->mode = 'CREATE';
        $this->name = $name;
        $this->exists = $this->exists ?? '';
        return $this;
    }
    public function drop(string $name): self
    {
        $this->mode = 'DROP';
        $this->name = $name;
        $this->exists = $this->exists ?? '';

        return $this;
    }
    public function dropIfExists(string $name): self
    {
        $this->mode = 'DROP';
        $this->name = $name;
        $this->exists = 'IF EXISTS';

        return $this;
    }
    public function ifExists(): self
    {
        $this->exists = 'IF EXISTS';
        return $this;
    }
    public function ifNotExists(): self
    {
        $this->exists = 'IF NOT EXISTS';
        return $this;
    }
    public function rename(string $old, string $new): self
    {
        $this->mode = 'RENAME';
        $this->name = $old;
        $this->rename = $new;
        $this->exists = $this->exists ?? '';

        return $this;
    }
    public function renameIfExists(string $old, string $new): self
    {
        $this->mode = 'RENAME';
        $this->name = $old;
        $this->rename = $new;
        $this->exists = 'IF EXISTS';

        return $this;
    }
    public function truncate(string $name): self
    {
        $this->mode = 'TRUNCATE';
        $this->name = $name;
        return $this;
    }

    // auto columns
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

    public function charset(string $charset): self
    {
        $this->constraints['charset'] = $charset;
        return $this;
    }
    public function collation(string $collation): self
    {
        $this->constraints['collation'] = $collation;
        return $this;
    }
    public function engine(string $engine): self
    {
        $this->constraints['engine'] = $engine;
        return $this;
    }
    public function strict(): self
    {
        $this->constraints['strict'] = 'strict';
        return $this;
    }
    public function withoutRowId(): self
    {
        $this->constraints['without rowid'] = 'without rowid';
        return $this;
    }

    // private function generateIndexName(array $columns): string
    // {
    //     return sprintf('%s_index_%s', $this->name, join('_', $columns));
    // }
    private function quote(string $str): string
    {
        return match ($this->dialect) {
            'mysql', 'mariadb' => sprintf('`%s`', $str),
            'pgsql', 'sqlite' => sprintf('"%s"', $str),
        };
    }
}
