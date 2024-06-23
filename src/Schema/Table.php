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
            $this->columns[$name] = $column;

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
            $this->indices[$name] = $index;

        }

    }
    public function alter(string $name): self
    {
        $this->mode = 'ALTER';
        $this->name = $name;
        return $this;
    }
    public function create(string $name): self
    {
        $this->mode = 'CREATE';
        $this->name = $name;
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
        $this->exists = '';

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
    public function drop(string $name): self
    {
        $this->mode = 'DROP';
        $this->name = $name;
        $this->exists = '';

        return $this;
    }
    public function dropIfExists(string $name): self
    {
        $this->mode = 'DROP';
        $this->name = $name;
        $this->exists = 'IF EXISTS';

        return $this;
    }


    // public function index(array|string $index): self
    // {
    //     if (is_array($index)) {
    //         $index = join(', ', $index);
    //     }
    //     $this->indices[] = $index;
    //     return $this;
    // }
    public function unique(array|string $index): self
    {

        return $this;
    }


    // auto columns
    public function softDeletes(): self
    {
        $this->columns['deleted_at'] = (new Column($this->dialect, 'deleted_at'))
            ->timestamp()
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
            join(",\n\t", $inds)
        );
    }

    private function generateIndexName(array $columns): string
    {
        return sprintf('%s_index_%s', $this->name, join('_', $columns));
    }
    private function quote(string $str): string
    {
        return match ($this->dialect) {
            'mysql', 'mariadb' => sprintf('`%s`', $str),
            'pgsql', 'sqlite' => sprintf('"%s"', $str),
        };
    }
}
