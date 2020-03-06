<?php namespace Zephyrus\Utilities\FileSystem;

class File extends FileSystemNode
{
    public static function create(string $path): self
    {
        $file = fopen($path,"wb");
        fclose($file);
        return new self($path);
    }

    public function getExtension()
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    public function read()
    {
        readfile($this->path);
    }

    public function write()
    {


    }

    public function append()
    {

    }

    /**
     * If the initial path is a single file, will remove it. If the given path
     * is a directory, it will completely empty it and then remove the
     * directory. Returns true on success and false on failure.
     */
    public function remove(): bool
    {
        return unlink($this->path);
    }

    /**
     * Returns the total file size of the specified directory or single file in
     * bytes.
     *
     * @return int
     */
    public function size(): int
    {
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
        return filemtime($this->path);
    }
}
