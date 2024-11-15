<?php

declare(strict_types=1);

namespace Phico\Database\Schema;

class Column
{
    /**
     * The database dialect to render
     * @var string
     */
    protected string $dialect;
    /**
     * The column name
     * @var string
     */
    protected string $name;
    /**
     * The column data type
     * @var string
     */
    protected string $type;
    /**
     * The column modification mode
     * create|alter|drop|rename
     * @var string
     */
    protected string $mode;
    /**
     * The new name of the column
     * @var string
     */
    protected string $rename;
    /**
     * Optional size specification for the column
     * @var string
     */
    protected string $size;
    /**
     * An array of column constraints
     * unsigned|not null|nullable|default|primary|auto_increment|comment
     * @var array
     */
    protected array $constraints = [];

    public function __construct(string $dialect, string $name)
    {
        $this->dialect = $dialect;
        $this->name = $name;

        // set defaults
        $this->mode = "create";
        $this->constraints["not null"] = "not null";
    }
    /**
     * Returns the DDL statement for this column.
     * @return string
     */
    public function toSql(): string
    {
        // quickly return the drop column statement
        if ($this->mode === "drop") {
            return sprintf("%s", $this->quote($this->name));
        }
        // quickly return the rename column statement
        if ($this->mode === "rename") {
            return sprintf(
                "%s TO %s",
                $this->quote($this->name),
                $this->quote($this->rename)
            );
        }

        // format the column size
        $size = isset($this->size) ? sprintf("(%d)", $this->size) : "";
        // sqlite does not support size
        if (in_array($this->dialect, ["sqlite"])) {
            $size = "";
        }

        // return alter statment
        if ($this->mode === "alter") {
            return match ($this->dialect) {
                // @TODO fix this SET / DROP constraints, change TYPE must be a separate statement
                "pgsql" => sprintf(
                    "%s TYPE %s%s %s,\nALTER COLUMN %s SET %s",
                    $this->quote($this->name),
                    $this->type,
                    $size,
                    $this->constraints(),
                    $this->quote($this->name),
                    $this->constraints()
                ),
                "mysql" => sprintf(
                    "%s %s%s %s",
                    $this->quote($this->name),
                    $this->type,
                    $size,
                    $this->constraints()
                ),
            };
        }

        // return create column statement
        return trim(
            sprintf(
                "%s %s %s",
                $this->quote($this->name),
                empty($size) ? $this->type : "{$this->type}{$size}",
                $this->constraints()
            )
        );
    }
    /**
     * Returns the render mode
     * @return string
     */
    public function mode(): string
    {
        return $this->mode;
    }
    /**
     * Returns true if the column is a primary column
     * @return bool
     */
    public function isPrimary(): bool
    {
        return array_key_exists("primary", $this->constraints);
    }
    /**
     * Returns the constraints as a formatted string
     * @return string
     */
    protected function constraints(): string
    {
        // set order
        $order = [
            "unsigned",
            "not null",
            "nullable",
            "default",
            "primary",
            "auto_increment",
            "comment",
        ];

        $out = [];
        foreach ($order as $c) {
            $out[] = array_key_exists($c, $this->constraints)
                ? $this->constraints[$c]
                : null;
        }

        return join(" ", array_filter($out));
    }
    /**
     * Returns a dialect appropriate quoted string
     * (This code is repeated in Table.php)
     * @param string $str The string to quote
     * @return string
     */
    protected function quote(string $str): string
    {
        return match ($this->dialect) {
            "mysql" => sprintf("`%s`", $str),
            "pgsql", "sqlite" => sprintf('"%s"', $str),
        };
    }

    // modes

    /**
     * Sets the render mode to alter
     * @return self
     */
    public function alter(): self
    {
        $this->mode = "alter";
        return $this;
    }
    /**
     * Sets the render mode to alter
     * @return self
     */
    public function change(): self
    {
        return $this->alter();
    }
    /**
     * Sets the render mode to drop
     * @return self
     */
    public function drop(): self
    {
        $this->mode = "drop";
        return $this;
    }
    /**
     * Sets the render mode to rename
     * @param string $name
     * @return self
     */
    public function rename(string $name): self
    {
        $this->mode = "rename";
        $this->rename = $name;
        return $this;
    }

    // column types

    /**
     * Sets the column type as binary
     * @return self
     */
    public function binary(): self
    {
        $this->type = match ($this->dialect) {
            "mysql" => "binary",
            "pgsql" => "bytea",
            "sqlite" => "blob",
        };
        return $this;
    }
    /**
     * Sets the column type as blob
     * @return self
     */
    public function blob(): self
    {
        $this->type = match ($this->dialect) {
            "mysql" => "blob",
            "pgsql" => "bytea",
            "sqlite" => "blob",
        };
        return $this;
    }
    /**
     * Sets the column type as boolean
     * @return self
     */
    public function boolean(): self
    {
        $this->type = match ($this->dialect) {
            "mysql" => "tinyint(1)",
            "pgsql" => "boolean",
            "sqlite" => "integer",
        };
        return $this;
    }

    // char types

    /**
     * Sets the column type as char
     * @param int $size An optional limit to the number of chars stored (defaults to 1)
     * @return self
     */
    public function char(int $size = 1): self
    {
        $this->type = match ($this->dialect) {
            "mysql" => "char",
            "pgsql" => "char",
            "sqlite" => "text",
        };
        $this->size = (string) $size;

        return $this;
    }
    /**
     * Sets the column type as string
     * @param int $size An optional limit to the number of chars stored (defaults to 255)
     * @return self
     */
    public function string(int $size = 255): self
    {
        return $this->varchar($size);
    }
    /**
     * Sets the column type as text
     * @param int $size An optional limit to the number of chars stored (defaults to 65535)
     * @return self
     */
    public function text(int $size = 65535): self
    {
        $this->type = match ($this->dialect) {
            "mysql" => "text",
            "pgsql" => "text",
            "sqlite" => "text",
        };
        $this->size = (string) $size;

        return $this;
    }
    /**
     * Sets the column type as variable character
     * @param int $size An optional limit to the number of chars stored (defaults to 255)
     * @return self
     */
    public function varchar(int $size = 255): self
    {
        $this->type = match ($this->dialect) {
            "mysql" => "varchar",
            "pgsql" => "varchar",
            "sqlite" => "text",
        };
        $this->size = (string) $size;

        return $this;
    }

    // date types

    /**
     * Sets the column type as date
     * @return self
     */
    public function date(): self
    {
        $this->type = match ($this->dialect) {
            "mysql" => "date",
            "pgsql" => "date",
            "sqlite" => "text",
        };
        return $this;
    }
    /**
     * Sets the column type as datetime
     * @return self
     */
    public function datetime(): self
    {
        $this->type = match ($this->dialect) {
            "mysql" => "datetime",
            "pgsql" => "datetime",
            "sqlite" => "text",
        };
        return $this;
    }
    /**
     * Sets the column type as time
     * @return self
     */
    public function time(): self
    {
        $this->type = match ($this->dialect) {
            "mysql" => "time",
            "pgsql" => "time",
            "sqlite" => "text",
        };
        return $this;
    }
    /**
     * Sets the column type as timestamp
     * @return self
     */
    public function timestamp(): self
    {
        $this->type = match ($this->dialect) {
            "mysql" => "timestamp",
            "pgsql" => "timestamp",
            "sqlite" => "integer",
        };
        return $this;
    }
    /**
     * Sets the column type as timestamp with timezone
     * @return self
     */
    public function timestampTz(): self
    {
        $this->type = match ($this->dialect) {
            "mysql" => "timestamp",
            "pgsql" => "timestamp_tz",
            "sqlite" => "integer",
        };
        return $this;
    }

    // integer types

    /**
     * Sets the column type as tiny integer
     * @param ?int $size An optional limit to the number of digits displayed
     * @return self
     */
    public function tinyInt(?int $size = null): self
    {
        if (!is_null($size)) {
            $this->size = (string) $size;
        }
        $this->type = match ($this->dialect) {
            "mysql" => "tinyint",
            "pgsql" => "smallint",
            "sqlite" => "integer",
        };
        return $this;
    }
    /**
     * Sets the column type as small integer
     * @param ?int $size An optional limit to the number of digits displayed
     * @return self
     */
    public function smallInt(?int $size = null): self
    {
        if (!is_null($size)) {
            $this->size = (string) $size;
        }
        $this->type = match ($this->dialect) {
            "mysql" => "smallint",
            "pgsql" => "smallint",
            "sqlite" => "integer",
        };
        return $this;
    }
    /**
     * Sets the column type as medium integer
     * @param ?int $size An optional limit to the number of digits displayed
     * @return self
     */
    public function mediumInt(?int $size = null): self
    {
        if (!is_null($size)) {
            $this->size = (string) $size;
        }
        $this->type = match ($this->dialect) {
            "mysql" => "mediumint",
            "pgsql" => "int",
            "sqlite" => "integer",
        };
        return $this;
    }
    /**
     * Sets the column type as big integer
     * @param ?int $size An optional limit to the number of digits displayed
     * @return self
     */
    public function bigInt(?int $size = null): self
    {
        if (!is_null($size)) {
            $this->size = (string) $size;
        }
        $this->type = match ($this->dialect) {
            "mysql" => "bigint",
            "pgsql" => "bigint",
            "sqlite" => "integer",
        };
        return $this;
    }
    /**
     * Sets the column type as integer
     * @param ?int $size An optional limit to the number of digits displayed
     * @return self
     */
    public function integer(?int $size = null): self
    {
        if (!is_null($size)) {
            $this->size = (string) $size;
        }
        $this->type = match ($this->dialect) {
            "mysql" => "integer",
            "pgsql" => "integer",
            "sqlite" => "integer",
        };
        return $this;
    }

    // float types

    /**
     * Sets the column type as double
     * @param ?int $int The precision of integer digits
     * @param ?int $fraction The precision of fractional digits
     * @return self
     */
    public function double(?int $int = null, ?int $fraction = null): self
    {
        if (!is_null($int)) {
            $this->size = sprintf("%d,%d", $int, $fraction);
        }
        $this->type = match ($this->dialect) {
            "mysql" => "double",
            "pgsql" => "double precision",
            "sqlite" => "real",
        };
        return $this;
    }
    /**
     * Sets the column type as float
     * @param ?int $int The precision of integer digits
     * @param ?int $fraction The precision of fractional digits
     * @return self
     */
    public function float(?int $int = null, ?int $fraction = null): self
    {
        if (!is_null($int)) {
            $this->size = sprintf("%d,%d", $int, $fraction);
        }
        $this->type = match ($this->dialect) {
            "mysql" => "float",
            "pgsql" => "real",
            "sqlite" => "real",
        };
        return $this;
    }
    /**
     * Sets the column type as decimal
     * @param ?int $int The precision of integer digits
     * @param ?int $fraction The precision of fractional digits
     * @return self
     */
    public function decimal(?int $int = null, ?int $fraction = null): self
    {
        if (!is_null($int)) {
            $this->size = sprintf("%d,%d", $int, $fraction);
        }
        $this->type = match ($this->dialect) {
            "mysql" => "decimal",
            "pgsql" => "numeric",
            "sqlite" => "numeric",
        };
        return $this;
    }
    /**
     * Sets the column type as numeric
     * @param ?int $int The precision of integer digits
     * @param ?int $fraction The precision of fractional digits
     * @return self
     */
    public function numeric(?int $int = null, ?int $fraction = null): self
    {
        if (!is_null($int)) {
            $this->size = sprintf("%d,%d", $int, $fraction);
        }
        $this->type = match ($this->dialect) {
            "mysql" => "numeric",
            "pgsql" => "numeric",
            "sqlite" => "numeric",
        };
        return $this;
    }

    // json

    /**
     * Sets the column type as JSON
     * @return self
     */
    public function json(): self
    {
        $this->type = match ($this->dialect) {
            "mysql" => "json",
            "pgsql" => "json",
            "sqlite" => "text",
        };
        return $this;
    }
    /**
     * Sets the column type as JSON Binary (Postgres only)
     * @return self
     */
    public function jsonb(): self
    {
        $this->type = match ($this->dialect) {
            "mysql" => "json",
            "pgsql" => "jsonb",
            "sqlite" => "text",
        };
        return $this;
    }

    // column constraints

    /**
     * Sets the 'auto increment' constraint
     * @return self
     */
    public function autoIncrement(): self
    {
        $this->constraints["auto_increment"] = match ($this->dialect) {
            "mysql" => "auto_increment",
            "pgsql" => "",
            "sqlite" => "autoincrement",
        };
        $this->constraints["not null"] = match ($this->dialect) {
            "mysql" => "not null",
            "pgsql" => "",
            "sqlite" => "not null",
        };
        $this->constraints["unsigned"] = match ($this->dialect) {
            "mysql" => "unsigned",
            "pgsql" => "",
            "sqlite" => "",
        };

        // Postgres column type should be serial
        if ($this->dialect === "pgsql") {
            $this->type = "serial";
            $this->type = "serial";
        }

        return $this;
    }
    /**
     * Sets the comment for the column
     * @param string $str The column comment
     * @return self
     */
    public function comment(string $str): self
    {
        $this->constraints["comment"] = sprintf('comment "%s"', $str);
        return $this;
    }
    /**
     * Sets the default value for the column
     * @param mixed $value The default value for the column
     * @return self
     */
    public function default(mixed $value): self
    {
        // don't quote int values
        // handle functions ( ... )
        // pgsql wants single quotes for strings
        $this->constraints["default"] = sprintf(
            "default %s",
            is_numeric($value) ? $value : sprintf('"%s"', $value)
        );
        return $this;
    }
    /**
     * Adds the 'not null' constraint
     * @return self
     */
    public function notNull(): self
    {
        $this->constraints["not null"] = "not null";
        return $this;
    }
    /**
     * Adds the 'nullable' constraint
     * @return self
     */
    public function nullable(): self
    {
        if (array_key_exists("not null", $this->constraints)) {
            unset($this->constraints["not null"]);
        }
        $this->constraints["nullable"] = "null";
        return $this;
    }
    /**
     * Adds the 'primary key' constraint
     * @return self
     */
    public function primary(): self
    {
        if ($this->dialect === "pgsql") {
            $this->constraints["primary"] = "";
        } else {
            // mysql, sqlite
            $this->constraints["primary"] = "primary key";
            $this->unsigned();
        }

        return $this;
    }
    /**
     * Sets the 'unsigned' constraint
     * @return self
     */
    public function unsigned(): self
    {
        if ($this->dialect === "mysql") {
            $this->constraints["unsigned"] = "unsigned";
        }
        return $this;
    }
    /**
     * Adds the 'use current' constraint
     * @return self
     */
    public function useCurrent(): self
    {
        $this->constraints["use_current"] = "use_current";
        return $this;
    }
}
