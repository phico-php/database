<?php

namespace Phico\Database\Schema;


class Column
{
    private string $dialect;
    private string $name;
    private string $type;
    private int $size;
    private bool $drop;
    private array $constraints = [];


    public function __construct(string $dialect, string $name)
    {
        $this->dialect = $dialect;
        $this->name = $name;

        // set defaults
        $this->constraints['not null'] = 'not null';
    }
    public function isPrimary(): bool
    {
        return array_key_exists('primary', $this->constraints);
    }
    public function __toString(): string
    {
        $size = isset($this->size) ? sprintf('(%d)', $this->size) : '';
        if (in_array($this->dialect, ['pgsql', 'sqlite'])) {
            $size = '';
        }

        return sprintf(
            '%s %s%s %s',
            $this->quote($this->name),
            $this->type,
            $size,
            $this->constraints()
        );
    }
    private function constraints(): string
    {
        // set order
        $order = ['unsigned', 'not null', 'nullable', 'default', 'auto_increment', 'primary', 'comment'];

        $out = [];
        foreach ($order as $c) {
            $out[] = (array_key_exists($c, $this->constraints)) ? $this->constraints[$c] : null;
        }

        return join(' ', array_filter($out));
    }
    private function quote(string $str): string
    {
        return match ($this->dialect) {
            'mysql', 'mariadb' => sprintf('`%s`', $str),
            'pgsql', 'sqlite' => sprintf('"%s"', $str),
        };
    }

    // actions

    public function drop(): self
    {
        $this->drop = true;
        return $this;
    }


    // column types

    public function binary(): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'binary',
            'pgsql' => 'bytea',
            'sqlite' => 'blob',
        };
        return $this;
    }

    public function blob(): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'blob',
            'pgsql' => 'bytea',
            'sqlite' => 'blob',
        };
        return $this;
    }

    public function boolean(): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'tinyint(1)',
            'pgsql' => 'boolean',
            'sqlite' => 'integer',
        };
        return $this;
    }

    // char types

    public function char(int $size): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => "char($size)",
            'pgsql' => "char($size)",
            'sqlite' => 'text',
        };
        $this->size = $size;

        return $this;
    }
    public function string(int $size): self
    {
        return $this->varchar($size);
    }
    public function text(int $size): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'text',
            'pgsql' => 'text',
            'sqlite' => 'text',
        };
        $this->size = $size;

        return $this;
    }
    public function varchar(int $size): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => "varchar($size)",
            'pgsql' => "varchar($size)",
            'sqlite' => 'text',
        };
        $this->size = $size;

        return $this;
    }

    // date types

    public function date(): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'date',
            'pgsql' => 'date',
            'sqlite' => 'text',
        };
        return $this;
    }
    public function datetime(): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'datetime',
            'pgsql' => 'datetime',
            'sqlite' => 'text',
        };
        return $this;
    }
    public function time(): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'time',
            'pgsql' => 'time',
            'sqlite' => 'text',
        };
        return $this;
    }
    public function timestamp(): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'timestamp',
            'pgsql' => 'timestamp',
            'sqlite' => 'integer',
        };
        return $this;
    }
    public function timestampTz(): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'timestamp',
            'pgsql' => 'timestamp_tz',
            'sqlite' => 'integer',
        };
        return $this;
    }

    // integer types

    public function tinyInt(?int $size = null): self
    {
        if (!is_null($size)) {
            $this->size = $size;
        }
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'tinyint',
            'pgsql' => 'smallint',
            'sqlite' => 'integer',
        };
        return $this;
    }
    public function smallInt(?int $size = null): self
    {
        if (!is_null($size)) {
            $this->size = $size;
        }
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'smallint',
            'pgsql' => 'smallint',
            'sqlite' => 'integer',
        };
        return $this;
    }
    public function mediumInt(?int $size = null): self
    {
        if (!is_null($size)) {
            $this->size = $size;
        }
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'mediumint',
            'pgsql' => 'int',
            'sqlite' => 'integer',
        };
        return $this;
    }
    public function bigInt(?int $size = null): self
    {
        if (!is_null($size)) {
            $this->size = $size;
        }
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'bigint',
            'pgsql' => 'bigint',
            'sqlite' => 'integer',
        };
        return $this;
    }
    public function integer(?int $size = null): self
    {
        if (!is_null($size)) {
            $this->size = $size;
        }
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'int',
            'pgsql' => 'integer',
            'sqlite' => 'integer',
        };
        return $this;
    }
    public function unsigned(): self
    {
        $this->constraints[] = 'unsigned';
        return $this;
    }

    // float types

    public function double(): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'double',
            'pgsql' => 'double precision',
            'sqlite' => 'real',
        };
        return $this;
    }
    public function float(): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'float',
            'pgsql' => 'real',
            'sqlite' => 'real',
        };
        return $this;
    }
    public function decimal(): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'decimal',
            'pgsql' => 'numeric',
            'sqlite' => 'numeric',
        };
        return $this;
    }
    public function numeric(): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'numeric',
            'pgsql' => 'numeric',
            'sqlite' => 'numeric',
        };
        return $this;
    }

    // json

    public function json(): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'json',
            'pgsql' => 'json',
            'sqlite' => 'text',
        };
        return $this;
    }
    public function jsonb(): self
    {
        $this->type = match ($this->dialect) {
            'mysql', 'mariadb' => 'json',
            'pgsql' => 'jsonb',
            'sqlite' => 'text',
        };
        return $this;
    }


    // column constraints


    public function autoIncrement(): self
    {
        $this->constraints['auto_increment'] = 'auto_increment';
        $this->constraints['not null'] = 'not null';
        $this->constraints['unsigned'] = 'unsigned';
        return $this;
    }
    public function comment(string $str): self
    {
        $this->constraints['comment'] = sprintf('comment "%s"', $str);
        return $this;
    }
    public function default(mixed $value): self
    {
        // don't quote int values
        // handle functions ( ... )
        // pgsql wants single quotes for strings
        $this->constraints['default'] = sprintf('default %s', (is_numeric($value)) ? $value : sprintf('"%s"', $value));
        return $this;
    }
    public function notNull(): self
    {
        $this->constraints['not null'] = 'not null';
        return $this;
    }
    public function nullable(): self
    {
        if (array_key_exists('not null', $this->constraints)) {
            unset($this->constraints['not null']);
        }
        $this->constraints['nullable'] = 'null';
        return $this;
    }
    public function primary(): self
    {
        $this->constraints['primary'] = 'primary key';
        return $this;
    }
    public function useCurrent(): self
    {
        $this->constraints['use_current'] = 'use_current';
        return $this;
    }

}
