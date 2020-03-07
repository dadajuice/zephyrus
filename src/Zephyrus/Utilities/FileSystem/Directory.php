<?php namespace Zephyrus\Utilities\FileSystem;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class Directory extends FileSystemNode
{
    /**
     * @var bool
     */
    private $recursive = true;

    /**
     * Creates a new directory and returns an instance of the newly created
     * directory.
     *
     * @param string $path
     * @param int $permission
     * @return Directory
     */
    public static function create(string $path, int $permission = 0777): self
    {
        mkdir($path, $permission, true);
        return new self($path);
    }

    /**
     * Constructs a Directory instance with the given root. Allows for recursive
     * features if the useRecursive is set to true (default). Otherwise, the
     * directory will only consider its first level of files for all features.
     *
     * @param string $directoryRoot
     * @param bool $useRecursive
     */
    public function __construct(string $directoryRoot, bool $useRecursive = true)
    {
        parent::__construct($directoryRoot);
        if (!is_dir($directoryRoot)) {
            throw new InvalidArgumentException("The specified path <$directoryRoot> is not a directory");
        }
        $this->recursive = $useRecursive;
    }

    /**
     * Iterates over every possible files and folders recursively by default
     * and sends each file's path into the given callback. Usage:
     *
     * $directory->scan(function($filepath) { ... });
     *
     * @param callable $callback
     */
    public function scan(callable $callback)
    {
        $this->scanRecursively($this->path, $callback);
    }

    /**
     * Retrieves all directory's available file names (without the directory
     * name by default).
     *
     * @param bool $includeDirectoryName
     * @return string[]
     */
    public function getFilenames(bool $includeDirectoryName = false): array
    {
        $files = [];
        $this->scanRecursively($this->path, function ($filepath) use (&$files, $includeDirectoryName) {
            if (!is_dir($filepath)) {
                $files[] = ($includeDirectoryName)
                    ? $filepath
                    : pathinfo($filepath, PATHINFO_BASENAME);
            }
        });
        return $files;
    }

    /**
     * Retrieves all directory's available files as File instances.
     *
     * @return File[]
     */
    public function getFiles(): array
    {
        $files = $this->getFilenames(true);
        return $this->pathToFiles($files);
    }

    /**
     * Searches for all file names that satisfies the given pattern inside
     * the directory.
     *
     * @param string $pattern
     * @param bool $includeDirectoryName
     * @return array
     */
    public function findFilenames(string $pattern, bool $includeDirectoryName = false): array
    {
        $directoryIterator = new RecursiveDirectoryIterator($this->path, RecursiveDirectoryIterator::SKIP_DOTS);
        $recursiveIterator = new RecursiveIteratorIterator($directoryIterator);
        $files = new RegexIterator($recursiveIterator, $pattern, RegexIterator::GET_MATCH);
        $fileList = [];
        foreach ($files as $file) {
            $fileList[] = ($includeDirectoryName)
                ? $file
                : pathinfo($file, PATHINFO_BASENAME);
        }
        return $fileList;
    }

    /**
     * Searches for all file instances that satisfies the given pattern inside
     * the directory.
     *
     * @param string $pattern
     * @return File[];
     */
    public function findFiles(string $pattern): array
    {
        $files = $this->findFilenames($pattern, true);
        return $this->pathToFiles($files);
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
        $this->scanRecursively($this->path, function ($element) use (&$totalSize) {
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
        $this->scanRecursively($directory, function ($element) {
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

    /**
     * Scans through every file recursively from a given directory and sends
     * the path of each file to the specified callback.
     *
     * @param string $directory
     * @param callable $callback
     */
    private function scanRecursively(string $directory, callable $callback)
    {
        $elements = scandir($directory);
        foreach ($elements as $element) {
            if ($element != "." && $element != "..") {
                $fullPath = $directory . DIRECTORY_SEPARATOR . $element;
                $callback($fullPath);
                if ($this->recursive && is_dir($fullPath)) {
                    $this->scanRecursively($fullPath, $callback);
                }
            }
        }
    }

    /**
     * Builds a list of File instances based on a given array of string file
     * paths.
     *
     * @param array $files
     * @return File[]
     */
    private function pathToFiles(array $files): array
    {
        $fileObjects = [];
        foreach ($files as $completeFilePath) {
            $fileObjects[] = new File($completeFilePath);
        }
        return $fileObjects;
    }
}
