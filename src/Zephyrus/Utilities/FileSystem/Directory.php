<?php namespace Zephyrus\Utilities\FileSystem;

use InvalidArgumentException;

class Directory extends FileSystemNode
{
    public static function create(string $path, int $mode = 0777): self
    {
        mkdir($path, $mode, true);
        return new self($path);
    }

    /**
     * Creates a FileSystem instance base on the given path which can be either
     * a directory root or a precise file. The public services (size, remove,
     * getLastModifiedTime, getOwner, getGroup) will adapt according to the path
     * type (file or folder). Will throw a InvalidArgumentException if the
     * given path is not reachable.
     *
     * @param string $directoryRoot
     */
    public function __construct(string $directoryRoot)
    {
        parent::__construct($directoryRoot);
        if (!is_dir($directoryRoot)) {
            throw new InvalidArgumentException("The specified path <$directoryRoot> is not a directory");
        }
    }

    /**
     *
     *
     * @param bool $recursive
     * @return array
     */
    public function getFiles(bool $recursive = true): array
    {

    }

    public function findFiles(string $pattern): array
    {

    }

    /**
     * If the initial path is a single file, will remove it. If the given path
     * is a directory, it will completely empty it and then remove the
     * directory. Returns true on success and false on failure.
     */
    public function remove(): bool
    {
        return $this->removeDirectory($this->path);
    }

    /**
     * Returns the total file size of the specified directory or single file in
     * bytes.
     *
     * @return int
     */
    public function size(): int
    {
        $totalSize = 0;
        $this->scanDirectoryCallback($this->path, function ($element) use (&$totalSize) {
            $totalSize += filesize($element);
        });
        return $totalSize;
    }

    /**
     * Obtains the last modification timestamp of the path/file defined in the
     * constructor. If its a directory, it will automatically fetch the latest
     * modified time.
     *
     * @return int
     */
    public function getLastModifiedTime(): int
    {
        return $this->getDirectoryLastModifiedTime($this->path);
    }

    /**
     * Recursively delete everything inside a given directory path. Makes sure
     * to ignore <.> and <..> navigation directory. Returns true on success or
     * false on failure.
     *
     * @param string $directory
     * @return bool
     */
    private function removeDirectory(string $directory): bool
    {
        $this->scanDirectoryCallback($directory, function ($element) {
            return (is_dir($element) && !is_link($element))
                ? $this->removeDirectory($element)
                : unlink($element);
        });
        return rmdir($directory);
    }

    /**
     * Obtains the last modification timestamp of the given directory path. It
     * will recursively fetch the most recent modification inside.
     *
     * @param string $rootDirectoryPath
     * @return int
     */
    private function getDirectoryLastModifiedTime(string $rootDirectoryPath): int
    {
        $lastModifiedTime = 0;
        $directoryLastModifiedTime = filemtime($rootDirectoryPath);
        foreach (glob("$rootDirectoryPath/*") as $file) {
            $fileLastModifiedTime = (is_file($file))
                ? filemtime($file)
                : $this->getDirectoryLastModifiedTime($file);
            $lastModifiedTime = max($fileLastModifiedTime, $directoryLastModifiedTime, $lastModifiedTime);
        }
        return $lastModifiedTime;
    }

    private function scanDirectoryCallback(string $directory, callable $callback)
    {
        $elements = scandir($directory);
        foreach ($elements as $element) {
            if ($element != "." && $element != "..") {
                $callback($directory . DIRECTORY_SEPARATOR . $element);
            }
        }
    }
}
