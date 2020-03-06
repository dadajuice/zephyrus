<?php namespace Zephyrus\Utilities;

use InvalidArgumentException;
use stdClass;

class FileSystem
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var bool
     */
    private $isDirectory;

    /**
     * Performs a normal glob pattern search, but enters directories recursively.
     *
     * @param string $pattern
     * @param int $flags
     * @return array
     */
    public static function recursiveGlob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, recursiveGlob($dir . '/' . basename($pattern), $flags));
        }
        return $files;
    }

    public static function recursiveCallback($directory, $callback)
    {
        if ($objs = glob($directory . "/*")) {
            foreach ($objs as $obj) {
                $callback($obj);
                if (is_dir($obj)) {
                    self::recursiveCallback($obj, $callback);
                }
            }
        }

        return $callback($directory);
    }

    public static function diskStats(string $partitionPath = "/var/www"): stdClass
    {
        $df = disk_free_space($partitionPath);
        $dt = disk_total_space($partitionPath);
        return (object) [
            'free' => $df,
            'total' => $dt,
            'used' => $dt - $df,
            'percent' => ($dt - $df) / $dt
        ];
    }

    public static function createFile()
    {

    }

    public static function createDirectory(string $path, int $mode = 0777): bool
    {
        return mkdir($path, $mode, true);
    }

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
        $this->path = $path;
        if (!file_exists($this->path)) {
            throw new InvalidArgumentException("The specified path <$path> does not exist");
        }
        $this->isDirectory = is_dir($this->path);
        $this->path = rtrim($this->path, DIRECTORY_SEPARATOR);
    }

    public function getAllFiles()
    {

    }

    public function exists($filename): bool
    {
        return false;
    }

    /**
     * If the initial path is a single file, will remove it. If the given path
     * is a directory, it will completely empty it and then remove the
     * directory. Returns true on success and false on failure.
     */
    public function remove(): bool
    {
        return ($this->isDirectory)
            ? $this->removeDirectory($this->path)
            : unlink($this->path);
    }

    /**
     * Returns the total file size of the specified directory or single file in
     * bytes.
     *
     * @return int
     */
    public function size(): int
    {
        if ($this->isDirectory) {
            $totalSize = 0;
            $this->scanDirectoryCallback($this->path, function ($element) use (&$totalSize) {
                $totalSize += filesize($element);
            });
            return $totalSize;
        }
        return filesize($this->path);
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
        return ($this->isDirectory)
            ? $this->getDirectoryLastModifiedTime($this->path)
            : filemtime($this->path);
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
