<?php namespace Zephyrus\Utilities;

class FileSystem
{
    private $path;
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

    public function __construct($path)
    {
        $this->path = $path;
        if (!file_exists($this->path)) {
            throw new \Exception("Specified path does not exist");
        }
        $this->isDirectory = is_dir($this->path);
        $this->path = rtrim($this->path, DIRECTORY_SEPARATOR);
    }

    /**
     * If the initial path is a single file, will remove it. If the given path
     * is a directory, it will completely empty it and then remove the directory.
     */
    public function remove()
    {
        if ($this->isDirectory) {
            $this->removeDirectory($this->path);
        } else {
            unlink($this->path);
        }
    }

    /**
     * Returns the total filesize of the specified directory or single file in
     * bytes.
     *
     * @return int
     */
    public function size()
    {
        if ($this->isDirectory) {
            $totalSize = 0;
            $files = scandir($this->path);
            foreach ($files as $backupFile) {
                if ($backupFile != '.' && $backupFile != '..') {
                    $totalSize += filesize($this->path . DIRECTORY_SEPARATOR . $backupFile);
                }
            }
            return $totalSize;
        }
        return filesize($this->path);
    }

    /**
     * Obtains the last modification timestamp of the given path. If its a
     * directory, it will recursively fetch the most recent modification
     * inside.
     *
     * @return int
     */
    public function getLastModifiedTime(?string $fileName = null)
    {
        if ($this->isDirectory) {
            $lastModifiedTime = 0;
            $directoryLastModifiedTime = filemtime($fileName ?? $this->path);
            foreach (glob("$this->path/*") as $file) {
                $fileLastModifiedTime = (is_file($file)) ? filemtime($file) : $this->getLastModifiedTime($file);
                $lastModifiedTime = max($fileLastModifiedTime, $directoryLastModifiedTime, $lastModifiedTime);
            }
            return $lastModifiedTime;
        }
        return filemtime($this->path);
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
     * Recursively delete everything inside a given directory path.
     */
    private function removeDirectory($dir)
    {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir."/".$object) && !is_link($dir."/".$object)) {
                    $this->removeDirectory($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        rmdir($dir);
    }
}
