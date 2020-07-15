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

    static public function exists(string $path): bool
    {
        return file_exists($path);
    }

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
     * Moves the specified directory or file to the given new path. If the new
     * path include new directories, they will be created. The new path must
     * include the complete filename is its a file.
     *
     * @param string $newPath
     */
    public function move(string $newPath)
    {
        rename($this->path, $newPath);
    }

    /**
     * Renames the specified directory or file to the given new name.
     *
     * @param string $newName
     */
    public function rename(string $newName)
    {
        $newPath = pathinfo($this->path, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . $newName;
        rename($this->path, $newPath);
    }

    /**
     * Copies the file to the destination. Creates all the requires directories
     * if needed.
     *
     * @param string $destination
     */
    public function copy(string $destination)
    {
        if (!file_exists(dirname($destination))) {
            mkdir(dirname($destination), 0777, true);
        }
        copy($this->path, $destination);
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
     * @codeCoverageIgnore
     * @return string
     */
    public function getOwner(): string
    {
        return posix_getpwuid(fileowner($this->path))['name'];
    }

    /**
     * Retrieves the current group of the file or directory specified.
     *
     * @codeCoverageIgnore
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
