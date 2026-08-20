<?php

declare(strict_types=1);


namespace DTL\OapiScg\Tests\Support;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class Workspace
{
    /**
     * @var string
     */
    private $path;

    private Filesystem $fs;

    public function __construct(string $path)
    {
        $this->path = Path::canonicalize($path);
        $this->fs = new Filesystem();
    }

    public static function create(string $path): self
    {
        if ('' ===$path) {
            throw new RuntimeException(
                'Workspace path cannot be empty'
            );
        }

        return new self($path);
    }

    public function exists(string $path): bool
    {
        return file_exists($this->path($path));
    }

    public function path(?string $path = null): string
    {
        if (null === $path) {
            return $this->path;
        }

        return Path::join($this->path, $path);
    }

    public function getContents(string $path): string
    {
        if (false === $this->exists($path)) {
            throw new InvalidArgumentException(sprintf(
                'File "%s" does not exist',
                $path
            ));
        }

        $contents = file_get_contents($this->path($path));

        if (false === $contents) {
            throw new RuntimeException('file_get_contents returned false');
        }

        return $contents;
    }

    public function reset(): void
    {
        if (file_exists($this->path)) {
            $this->fs->remove($this->path);
        }

        mkdir($this->path);
    }

    public function put(string $path, string $contents): Workspace
    {
        if (!$this->exists(dirname($path))) {
            $this->mkdir(dirname($path));
        }

        file_put_contents($this->path($path), $contents);

        return $this;
    }

    public function mkdir(string $path): Workspace
    {
        $path = $this->path($path);

        if (file_exists($path)) {
            throw new InvalidArgumentException(sprintf(
                'Node "%s" already exists, cannot create directory',
                $path
            ));
        }

        mkdir($path, 0o0777, true);

        return $this;
    }
}
