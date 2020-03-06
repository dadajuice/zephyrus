<?php namespace Zephyrus\Utilities\FileSystem;

use InvalidArgumentException;

abstract class FileSystemNode
{
    /**
     * @var string
     */
    protected $path;

    abstract public function remove(): bool;
    abstract public function size(): int;
    abstract public function getLastModifiedTime(): int;

    /**
     * Creates a FileSystem instance base on the given path which can be either
     * a directory root or a precise file. The public services (size, remove,
     * getLastModifiedTime, getOwner, getGroup) will adapt according to the path
     * type (file or folder). Will throw a InvalidArgumentException if the
     * given path is not reachable.
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException("The specified path <$path> does not exist");
        }
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);
    }

    public function rename(string $newPath)
    {
        rename($this->path, $newPath);
    }

    /**
     * Fetches the current owner of the file or directory specified.
     *
     * @return string
     */
    public function getOwner(): string
    {
        return posix_getpwuid(fileowner($this->path))['name'];
    }

    /**
     * Fetches the current group of the file or directory specified.
     *
     * @return string
     */
    public function getGroup(): string
    {
        return posix_getpwuid(filegroup($this->path))['name'];
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
