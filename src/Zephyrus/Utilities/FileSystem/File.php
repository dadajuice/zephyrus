<?php namespace Zephyrus\Utilities\FileSystem;

class File extends FileSystemNode
{
    public static function create(string $path): self
    {
        touch($path);
        return new self($path);
    }

    /**
     * Retrieves the file extension (e.g. /var/www/project/martin.txt would
     * return "txt").
     *
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * Retrieves the name of the file without the extension (e.g
     * /var/www/project/martin.txt would return "martin").
     *
     * @return string
     */
    public function getFilename(): string
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * Retrieves the name of the file and its extension.
     *
     * @return string
     */
    public function getBasename(): string
    {
        return pathinfo($this->path, PATHINFO_BASENAME);
    }

    /**
     * Retrieves the name of the file's directory.
     *
     * @return string
     */
    public function getDirectoryName(): string
    {
        return pathinfo($this->path, PATHINFO_DIRNAME);
    }

    /**
     * Creates a Directory instance of the file's directory.
     *
     * @return Directory
     */
    public function getDirectory(): Directory
    {
        return new Directory($this->getDirectoryName());
    }

    /**
     * Retrieves the real file mime type (using finfo) and not considering
     * the file extension.
     *
     * @return string
     */
    public function getMimeType(): string
    {
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($info, $this->path);
        finfo_close($info);
        return $mime;
    }

    /**
     * Outputs the file content to the buffer. Equivalent of doing an "echo" of
     * the entire data to the response.
     */
    public function output()
    {
        readfile($this->path);
    }

    /**
     * Returns the entire file content into a string. Should not be used with
     * huge files. Consider using the "output" method for displaying very huge
     * file content to the response buffer.
     *
     * @return string
     */
    public function read(): string
    {
        return file_get_contents($this->path);
    }

    /**
     * Overwrites the file actual content with the given data. The data can be
     * string, array or resource stream.
     *
     * @param mixed $data
     */
    public function write($data)
    {
        file_put_contents($this->path, $data);
    }

    /**
     * Appends the given data at the end of the actual file content. The data
     * can be string, array or resource stream.
     *
     * @param $data
     */
    public function append($data)
    {
        file_put_contents($this->path, $data, FILE_APPEND);
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

    /**
     * Updates the modification time of the file to the given timestamp or
     * simply the current time if none is supplied.
     *
     * @param int|null $timestamp
     */
    public function touch(?int $timestamp = null)
    {
        touch($this->path, $timestamp ?? time());
    }

    /**
     * Opens the file in memory and returns the resource handle for manual
     * manipulations of the file. Will need to be manually closed outside of
     * this class. The available modes are the one used with fopen.
     *
     * @see https://www.php.net/manual/en/function.fopen.php
     * @param string $mode
     * @return false|resource
     */
    public function getHandle(string $mode = "r")
    {
        return fopen($this->path, $mode);
    }
}
