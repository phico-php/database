<?php

declare(strict_types=1);

namespace Phico\Database\Commands;

use Phico\Cli\{Args, Cli};
use Phico\Database\DB;
use Phico\Database\Schema\Seed;


class Seeds extends Cli
{
    protected string $help = 'Usage: phico database seeds (create|list|run) [name]';
    protected string $path;
    protected DB $db;


    public function __construct()
    {
        $this->path = config('database.seeds.path', 'resources/seeds');
        $this->db = db(config('database.seeds.connection', 'default'));
    }

    public function create(Args $args)
    {
        $name = $args->index(0) ?? $args->value('name');
        if (is_null($name)) {
            throw new \InvalidArgumentException('Please provide the name of the seed to create');
        }

        // create the class name
        $name = preg_replace(['/[^a-z0-9]/i', '/.php$/i'], ' ', $name);
        $name = str_replace(' ', '', str()->toCamelCase($name));

        // create the filename
        $filename = sprintf("%s.php", str()->toCamelCase($name));

        // create the files instance
        $file = files(path("$this->path/$filename"));
        if ($file->exists()) {
            throw new \InvalidArgumentException(sprintf("A seed with the name '%s' exists at '%s', please delete it if you want to create a new seed with the same name", $name, $filename));
        }

        $file->write($this->getTemplate($name));

        $this->write("Created seed at '$this->path/$filename'");
    }
    public function list(Args $args)
    {
        $this->title("Showing seeds");

        $list = folders($this->path)->list();
        if (empty($list)) {
            $this->write(sprintf("No seeds found in '%s'\n", path($this->path)));
            exit(1);
        }
        foreach ($list as $filename) {
            $this->write($filename);
        }
    }
    public function run(Args $args)
    {
        $name = $args->index(4) ?? $args->value('name');
        if (is_null($name)) {
            throw new \InvalidArgumentException('Please provide the name of the seed to run');
        }

        $filename = ltrim(str_replace(path($this->path), '', $name), '/');

        if (!files(path("$this->path/$filename"))->exists()) {
            $this->error("\nCannot find seed file '$filename' in '$this->path'\n");
            exit(1);
        }

        try {

            $this->write("Running Seed $filename ... ", false);

            $ts = microtime(true);

            $this->db->startTransaction();

            // get class instance and call run()
            $seed = $this->instantiate($filename);
            $seed->seed();

            $this->db->finishTransaction();

            $this->write("done");

            if ($args->has('t')) {
                $this->success(sprintf("Time: %2fs", microtime(true) - $ts));
            }

        } catch (\Throwable $e) {

            $this->db->cancelTransaction();

            throw $th;

        }
    }
    private function instantiate(string $filename): Seed
    {
        $content = files(path("$this->path/$filename"))->read();
        if (false === preg_match('/\s?(final class|class)\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/i', $content, $matches)) {
            throw new \RuntimeException(sprintf('Cannot get class name from seed file at %s', path("$this->path/$filename")));
        }
        if (!isset($matches[2])) {
            throw new \RuntimeException(sprintf('Cannot get class name from seed file at %s', path("$this->path/$filename")));
        }

        require_once path("$this->path/$filename");

        $classname = sprintf('\App\Resources\Seeds\%s', $matches[2]);
        return new $classname($this->db);
    }
    private function getTemplate(string $name): string
    {
        $name = str_replace(' ', '', (ucwords(str_replace('_', ' ', $name))));

        return sprintf(
            "<?php\n\ndeclare(strict_types=1);\n%s%s\n%s\n{%s\n}\n",
            // namespace
            "\nnamespace App\Resources\Seeds;\n",
            // use statements
            "\nuse Phico\Database\Schema\Seed;\n",
            // class header
            "\nfinal class $name extends Seed",
            // up method
            "\n\tpublic function seed(): void\n\t{\n\t\t\$this->db->raw(\"\n\n\t\t\");\n\t}"
        );
    }
}
