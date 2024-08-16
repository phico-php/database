<?php

declare(strict_types=1);

namespace Phico\Database\Commands;

use Phico\Cli\{Args, Cli};
use Phico\Database\DB;
use Phico\Database\Schema\Migration;


class Migrations extends Cli
{
    protected string $help = 'Usage: phico database migrations (init|create|todo|do|done|undo|drop) [name]';
    protected string $table;
    protected string $path;
    protected DB $db;


    public function __construct()
    {
        $this->table = config()->get('database.migrations.table', '_migrations');
        $this->path = config()->get('database.migrations.path', 'resources/migrations');
        $this->db = db(config()->get('database.use', 'default'));
    }
    public function create(Args $args): void
    {
        $this->requireMigrationsTable();

        $name = $args->index(0) ?? $args->value('name');
        if (is_null($name)) {
            throw new \InvalidArgumentException('Please provide the name of the migration to create');
        }

        // create the class name
        $name = preg_replace(['/[^a-z0-9]/i', '/.php$/i'], ' ', $name);
        $name = str_replace(' ', '', str()->toCamelCase($name));

        // create the filename
        $filename = sprintf("%s_%s.php", time(), str()->toSnakeCase($name));

        // sods law
        if (file_exists(path("$this->path/$filename"))) {
            sleep(1);
        }

        files(path("$this->path/$filename"))->write($this->getTemplate($name));

        $this->success("Created migration at '$this->path/$filename'");

    }
    public function init(): void
    {

        if ($this->tableExists($this->table)) {
            $this->info("The migrations table '$this->table' has already been created");
            return;
        }

        $this->info("Initialising the migrations table using '$this->table'");

        $sql = match ($this->db->driver) {
            'sqlite' => "CREATE TABLE IF NOT EXISTS $this->table
                    (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        filename VARCHAR(255) NOT NULL,
                        sequence INTEGER NOT NULL,
                        version VARCHAR(15) NOT NULL,
                        timestamp INTEGER NOT NULL
                    );",
            'pgsql', 'psql' => "CREATE TABLE IF NOT EXISTS public.$this->table
                    (
                        id serial PRIMARY KEY,
                        filename character varying(255) NOT NULL,
                        sequence integer NOT NULL,
                        version character varying(15) NOT NULL,
                        timestamp integer NOT NULL
                    );",
            'mysql', 'mariadb' => "CREATE TABLE IF NOT EXISTS $this->table
                    (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        filename VARCHAR(255) NOT NULL,
                        sequence INT NOT NULL,
                        version VARCHAR(15) NOT NULL,
                        timestamp INT NOT NULL
                    );"
        };

        // create migrations table
        $this->db->raw($sql);
        $this->success("Created the migratons table, now create and run your migrations.\n");
    }
    public function drop(): void
    {
        $this->requireMigrationsTable();

        $in = $this->prompt('Are you sure you want to remove the migrations table? (NO/yes) ', ['no', 'yes']);
        if (!str_starts_with($in, 'y')) {
            return;
        }

        if (count($this->getMigrationRows())) {
            throw new \RuntimeException("Cannot drop the migrations table while it contains completed migrations");
        }

        $this->write("Dropping the migrations table '$this->table' ... ", false);
        $this->db->raw("DROP TABLE IF EXISTS $this->table;");
        $this->write('done');

        $this->success("Dropped the migrations table successfully, run 'phico database migrations init' to create it again");
    }
    public function todo(Args $args): void
    {

        $this->requireMigrationsTable();

        $this->title("Pending migrations");

        $ts = microtime(true);

        $migrations = $this->getPendingMigrations();
        if (0 === count($migrations)) {
            $this->write("No migrations todo\n");
            return;
        }

        $i = 0;
        foreach ($migrations as $filename) {
            $this->write(sprintf("%d. %s", ++$i, $filename));
        }

        $this->write("\n");

        if ($args->has('t')) {
            $this->success(sprintf("Time: %2fs", microtime(true) - $ts));
        }

    }
    public function do(Args $args): void
    {
        try {

            $this->requireMigrationsTable();

            $this->title("Running migrations");

            // fetch next sequence number
            $sequence = $this->db
                ->execute("SELECT max(sequence) + 1 FROM $this->table")
                ->fetchColumn(0);
            if (!$sequence) {
                $sequence = 1;
            }

            $ts = microtime(true);

            $migrations = $this->getPendingMigrations();
            if (0 === count($migrations)) {
                die("No migrations to do\n");
            }

            $this->db->startTransaction();

            $i = 0;
            foreach ($migrations as $filename) {

                $this->write(sprintf("%d. %s ... ", ++$i, $filename), false);

                // get class instance and call up()
                $migration = $this->instantiate($filename);
                $migration->up();

                // save successful migration in db
                $this->db->execute("INSERT INTO $this->table (sequence, filename, timestamp, version) VALUES (:sequence, :filename, :timestamp, :version)", [
                    'sequence' => $sequence,
                    'filename' => $filename,
                    'timestamp' => time(),
                    'version' => $sequence
                ]);

                $this->write("done");
            }

            $this->db->finishTransaction();

            $this->write("\n");

            if ($args->has('t')) {
                $this->success(sprintf("Time: %2fs", microtime(true) - $ts));
            }

        } catch (\Throwable $th) {

            $this->db->cancelTransaction();

            throw $th;

        }

    }
    public function done(Args $args): void
    {

        $this->requireMigrationsTable();

        $this->title("Completed migrations");

        $ts = microtime(true);

        $migrations = $this->getMigrationRows();
        if (0 === count($migrations)) {
            $this->info("No completed migrations\n");
        }

        $i = 0;
        foreach ($migrations as $filename) {
            $this->write(sprintf("%d. %s", ++$i, $filename));
        }

        $this->write("\n");

        if ($args->has('t')) {
            $this->success(sprintf("Time: %2fs", microtime(true) - $ts));
        }

    }
    public function undo(Args $args): void
    {
        try {

            $this->requireMigrationsTable();

            $this->info("Undoing the most recent migrations...");

            $ts = microtime(true);

            // fetch the most recent sequence number
            $sequence = $this->db
                ->execute("SELECT max(sequence) FROM $this->table")
                ->fetchColumn(0);
            if (is_null($sequence)) {
                $this->info("No migrations to undo\n");
                return;
            }

            $this->db->startTransaction();

            $i = 0;
            foreach ($this->getMigrationsInSequence($sequence) as $filename) {

                $this->write(sprintf("%d. %s ... ", ++$i, $filename), false);

                // get class instance and call down()
                $migration = $this->instantiate($filename);
                $migration->down();

                // remove migration from db
                $this->db->execute("DELETE FROM $this->table WHERE filename=:filename", [
                    'filename' => $filename,
                ]);

                $this->write("reverted");
            }

            $this->db->finishTransaction();

            $this->write("\n");

            if ($args->has('t')) {
                $this->success(sprintf("Time: %2fs", microtime(true) - $ts));
            }

        } catch (\Throwable $th) {

            $this->db->cancelTransaction();

            throw $th;

        }
    }

    // show a warning if the migrations table does not exist
    private function requireMigrationsTable(): void
    {
        if (!$this->tableExists($this->table)) {
            $this->warning("The migrations table does not exist, create it using './vendor/bin/pico db migrations init'\n");
            exit(1);
        }
    }

    private function getTemplate(string $name): string
    {
        $name = str_replace(' ', '', (ucwords(str_replace('_', ' ', $name))));

        return sprintf(
            "<?php\n\ndeclare(strict_types=1);\n%s%s\n%s\n{%s%s\n}\n",
            // namespace
            "\nnamespace App\Resources\Migrations;\n",
            // use statements
            "\nuse Phico\Database\Schema\Migration;\n",
            // class header
            "\nfinal class $name extends Migration",
            // up method
            "\n\tpublic function up(): void\n\t{\n\t\t\$table = table();\n\t\t\$this->db->raw(\$table->toSql());\n\t}",
            // down method
            "\n\tpublic function down(): void\n\t{\n\t\t\$table = table();\n\t\t\$this->db->raw(\$table->toSql());\n\t}"
        );
    }
    private function tableExists(string $table): bool
    {
        $db_name = ''; // @TODO fetch db name from config for mysql

        $sql = match ($this->db->driver) {
            'sqlite' => "SELECT name FROM sqlite_master WHERE type='table' AND name='$table';",
            'pgsql', 'psql' => "SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = '$table')",
            'mysql', 'mariadb' => "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$db_name'  AND table_name = '$table';"
        };

        $stmt = $this->db->execute($sql);
        // @TODO check this on mysql, (sqlite & pgsql ok)
        return $stmt->fetchColumn(0) === $table;
    }
    private function getMigrationFiles(): array
    {
        $out = [];
        $folders = folders(path($this->path));
        foreach ($folders->list() as $file) {
            if (substr($file, -4) == '.php') {
                $out[] = $file;
            }
        }
        return $out;
    }
    private function getMigrationRows(): array
    {
        $out = [];
        $items = $this->db
            ->execute("SELECT * FROM $this->table ORDER BY timestamp ASC, filename ASC")
            ->fetchAll(\PDO::FETCH_OBJ);
        foreach ($items as $item) {
            $out[] = $item->filename;
        }
        return $out;
    }
    private function getPendingMigrations(): array
    {
        // return difference between migrations on disk and in database table
        return array_diff($this->getMigrationFiles(), $this->getMigrationRows());
    }
    private function getMigrationsInSequence(int $sequence): array
    {
        $out = [];
        $items = $this->db
            ->execute("SELECT * FROM $this->table WHERE sequence=:sequence ORDER BY timestamp ASC, filename ASC", [
                'sequence' => $sequence
            ])->fetchAll(\PDO::FETCH_OBJ);
        foreach ($items as $item) {
            $out[] = $item->filename;
        }
        return $out;
    }
    private function instantiate(string $filename): Migration
    {
        $content = files(path("$this->path/$filename"))->read();
        if (false === preg_match('/\s?(final class|class)\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/i', $content, $matches)) {
            throw new \RuntimeException(sprintf('Cannot get class name from migration file at %s', path("$this->path/$filename")));
        }
        if (!isset($matches[2])) {
            throw new \RuntimeException(sprintf('Cannot get class name from migration file at %s', path("$this->path/$filename")));
        }

        require_once path("$this->path/$filename");

        $classname = sprintf('\App\Resources\Migrations\%s', $matches[2]);
        return new $classname($this->db);
    }
}
