<?php

declare(strict_types=1);

namespace Phico\Database\Commands;

use Phico\Cli\{Args, Cli};
use Phico\Database\DB;
use Phico\Database\Seed;


class Database extends Cli
{
    protected string $help = 'Usage: phico database use [connection name]';
    protected string $path;
    protected DB $db;


    public function use(Args $args)
    {
        if (php_sapi_name() !== 'cli') {
            die("This script can only be run from the command line.");
        }

        $use = config('database.use');
        $config = config("database.connections.$use");

        $cmd = match ($config['driver']) {

            'mysql' => sprintf(
                'mysql -u%s -p%s -h%s -D%s',
                escapeshellarg($config['username']),
                escapeshellarg($config['password']),
                escapeshellarg($config['host']),
                escapeshellarg($config['database']),
            ),

            'psql' => sprintf(
                'PGPASSWORD=%s psql -U %s -h %s -p %s -d %s',
                escapeshellarg($config['password']),
                escapeshellarg($config['username']),
                escapeshellarg($config['host']),
                escapeshellarg($config['port']),
                escapeshellarg($config['database']),
            ),

            'sqlite' => sprintf('sqlite3 %s', path(escapeshellarg($config['database']))),

            default => throw new \RuntimeException(sprintf("Unsupported driver '%s' for `database use`", $config['driver'])),
        };

        $redirects = [
            0 => ["file", "php://stdin", "r"],  // stdin
            1 => ["file", "php://stdout", "w"], // stdout
            2 => ["file", "php://stderr", "w"]  // stderr
        ];
        $pipes = [];
        $cwd = path();
        $env = null;

        $this->showCheatsheet($config['driver']);

        $proc = proc_open($cmd, $redirects, $pipes, $cwd, $env);

        if (is_resource($proc)) {
            proc_close($proc);
        } else {
            $this->error("Unable to start an interactive database session");
        }
    }

    private function showCheatsheet(string $driver): void
    {

        if ($driver === 'sqlite') {

            $this->title('SQLite Cheat Sheet');
            $this->table([
                ['.help', 'Show help'],
                ['.databases', 'Show databases'],
                ['.tables', 'Show tables'],
                ['.schema (table_name)', 'Show schema, optionally for table_name'],
                ['.headers (on|off)', 'Show column headers in output'],
                ['.mode (column|csv|html|insert|line|list|tabs|tcl)', 'Change output format'],
                ['.import data.csv table_name', 'Import data (to table)'],
                ['.output (stdout|results.txt)', 'Set output'],
                ['.dump (table_name)', 'Dump data (for table)'],
                ['.open /path/to/database', 'Open a database'],
                ['.read /path/to/commands.sql', 'Execute the commands in a file'],
                ['.load /path/to/extension', 'Load an extension'],
                ['.quit', 'Quit'],
            ]);
            $this->write("\n");

        }

        if ($driver === 'psql') {

            $this->title('PostgreSQL Cheat Sheet');
            $this->table([
                ['\\?', 'Show help'],
                ['\\c [DBNAME | USER | HOST | PORT]', 'Connect to a new database'],
                ['\\d [NAME]', 'Describe table, view, sequence, or index'],
                ['\\dt', 'List tables'],
                ['\\l', 'List databases'],
                ['\\du', 'List roles (users)'],
                ['\\df', 'List functions'],
                ['\\dv', 'List views'],
                ['\\dn', 'List schemas'],
                ['\\di', 'List indexes'],
                ['\\dg', 'List groups'],
                ['\\dp [PATTERN]', 'Show access privileges'],
                ['\\x [on|off]', 'Toggle expanded display mode'],
                ['\\o [FILE]', 'Send output to file or stdout'],
                ['\\q', 'Quit'],
                ['\\i FILENAME', 'Execute commands from file'],
                ['\\e [FILENAME]', 'Edit query buffer or file with external editor'],
                ['\\! [COMMAND]', 'Execute a shell command'],
                ['\\timing', 'Toggle display of query execution time'],
                ['\\set', 'Set a psql variable'],
                ['\\unset', 'Unset (delete) a psql variable'],
            ]);
            $this->write("\n");
        }
    }
}
