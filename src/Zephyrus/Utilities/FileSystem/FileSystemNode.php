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
     * Constructs an abstract FileSystemNode which is used by the Directory
     * and File classes. Throws an InvalidArgumentException if the given path
     * is non existent.
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

    /**
     * Renames the specified directory or file to the given new name. If the new
     * path include new directory, they will be created.
     *
     * @param string $newPath
     */
    public function rename(string $newPath)
    {
        rename($this->path, $newPath);
    }

    /**
     * Returns the parent directory's path of the specified file or directory
     * currently in use. Specifying the level allows to go back several
     * directories at once.
     *
     * @param int $level
     * @return string
     */
    public function parent(int $level = 1): string
    {
        return dirname($this->path, $level);
    }

    /**
     * Retrieves the current owner of the file or directory specified.
     *
     * @return string
     */
    public function getOwner(): string
    {
        return posix_getpwuid(fileowner($this->path))['name'];
    }

    /**
     * Retrieves the current group of the file or directory specified.
     *
     * @return string
     */
    public function getGroup(): string
    {
        return posix_getpwuid(filegroup($this->path))['name'];
    }

    /**
     * Retrieves the specified path of the file or directory defined for the
     * instance construction.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Retrieves the complete real path (evaluates ., .. and symlinks) of the
     * specified instance path.
     *
     * @return string
     */
    public function getRealPath(): string
    {
        return realpath($this->path);
    }
}
