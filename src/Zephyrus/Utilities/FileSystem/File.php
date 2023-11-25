<?php namespace Zephyrus\Utilities\FileSystem;

use CURLFile;
use Zephyrus\Security\Cryptography;

class File extends FileSystemNode
{
    /**
     * Creates a new file and returns an instance of the newly created file. Will throw an exception if the override
     * argument is set to false and the file already exists.
     *
     * @param string $path
     * @param bool $overwrite
     * @return File
     */
    public static function create(string $path, bool $overwrite = false): self
    {
        if (!$overwrite && self::exists($path)) {
            throw new \InvalidArgumentException("Specified file <$path> already exists");
        }
        if ($overwrite && self::exists($path)) {
            (new self($path))->remove();
        }
        touch($path);
        return new self($path);
    }

    /**
     * Retrieves the file extension (e.g. /var/www/project/martin.txt would return "txt").
     *
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * Retrieves the name of the file without the extension (e.g /var/www/project/martin.txt would return "martin").
     *
     * @return string
     */
    public function getFilename(): string
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * Retrieves the name of the file and its extension (e.g /var/www/project/martin.txt would return "martin.txt").
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
     * Retrieves the real file mime type (using finfo) and not considering the file extension.
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
     * Outputs the file content to the buffer. Equivalent of doing an "echo" of the entire data to the response.
     */
    public function output()
    {
        readfile($this->path);
    }

    /**
     * Returns the entire file content into a string. Should not be used with huge files. Consider using the "output"
     * method for displaying very huge file content to the response buffer.
     *
     * @return string
     */
    public function read(): string
    {
        return file_get_contents($this->path);
    }

    /**
     * Overwrites the file actual content with the given data. The data can be string, array or resource stream.
     *
     * @param mixed $data
     */
    public function write($data)
    {
        file_put_contents($this->path, $data);
    }

    /**
     * Appends the given data at the end of the actual file content. The data can be string, array or resource stream.
     *
     * @param $data
     */
    public function append($data)
    {
        file_put_contents($this->path, $data, FILE_APPEND);
    }

    /**
     * Encrypts the content of the file and either save it directly or create a new file at the given $destination that
     * will hold the ciphertext result. Useful if the original file needs to be kept intact somehow. Dont forget the
     * key as it will be needed to decrypt the file.
     *
     * @param string $key
     * @param string|null $destination
     */
    public function encrypt(string $key, ?string $destination = null)
    {
        $clearTextContent = $this->read();
        $cipher = Cryptography::encrypt($clearTextContent, $key);
        if (!is_null($destination)) {
            $file = File::create($destination, true);
            $file->write($cipher);
        } else {
            $this->write($cipher);
        }
    }

    /**
     * Decrypts the content of the file and either save it directly or create a new file at the given $destination that
     * will hold the clear text result. Useful if the original file needs to stay encrypted. Needs the same key used
     * to encrypt.
     *
     * @param string $key
     * @param string|null $destination
     */
    public function decrypt(string $key, ?string $destination = null)
    {
        $cipher = $this->read();
        $clearTextContent = Cryptography::decrypt($cipher, $key);
        if (!is_null($destination)) {
            $file = File::create($destination, true);
            $file->write($clearTextContent);
        } else {
            $this->write($clearTextContent);
        }
    }

    /**
     * Returns the md5 hash of the file (can be useful for Etags or integrity checks).
     *
     * @return string
     */
    public function md5(): string
    {
        return md5_file($this->path);
    }

    /**
     * Returns the sha1 hash of the file (can be useful for Etags or integrity checks).
     *
     * @return string
     */
    public function sha1(): string
    {
        return sha1_file($this->path);
    }

    /**
     * Returns the base64 encode format for inclusion as standard uri (e.g. in <img> tags).
     *
     * @return string
     */
    public function base64Uri(): string
    {
        $mime = $this->getMimeType();
        $data = $this->read();
        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /**
     * Creates an instance of CURLFile based on the current file destined to be used in a Curl upload session. If no
     * uploadFilename is given, the original filename will be used (including extension).
     *
     * @param string|null $uploadFilename
     * @return CURLFile
     */
    public function buildCurlFile(?string $uploadFilename = null): CURLFile
    {
        if (is_null($uploadFilename)) {
            $uploadFilename = $this->getBasename();
        } else {
            $givenExtension = pathinfo($uploadFilename, PATHINFO_EXTENSION);
            if (empty($givenExtension)) {
                $extension = $this->getExtension();
                if (!empty($extension)) {
                    $uploadFilename .= '.' . $extension;
                }
            }
        }
        return new CURLFile($this->path, $this->getMimeType(), $uploadFilename);
    }

    /**
     * If the initial path is a single file, will remove it. If the given path is a directory, it will completely empty
     * it and then remove the directory. Returns true on success and false on failure.
     */
    public function remove(): bool
    {
        return unlink($this->path);
    }

    /**
     * Returns the total file size of the specified directory or single file in bytes.
     *
     * @return int
     */
    public function size(): int
    {
        return filesize($this->path);
    }

    /**
     * Obtains the last modification timestamp of the path/file defined in the constructor. If its a directory, it will
     * automatically fetch the latest modified time.
     *
     * @return int
     */
    public function getLastModifiedTime(): int
    {
        return filemtime($this->path);
    }

    /**
     * Obtains the last access timestamp of the path/file defined in the constructor. If its a directory, it will
     * automatically fetch the latest accessed time.
     *
     * @return int
     */
    public function getLastAccessedTime(): int
    {
        return fileatime($this->path);
    }

    /**
     * Updates the modification time of the file to the given timestamp or simply the current time if none is supplied.
     *
     * @param int|null $updateTimestamp
     * @param int|null $accessTimestamp
     */
    public function touch(?int $updateTimestamp = null, ?int $accessTimestamp = null): void
    {
        touch($this->path, $updateTimestamp ?? time(), $accessTimestamp ?? time());
    }

    /**
     * Updates only the last access time of the file. Make sure to keep the previous last modification time.
     *
     * @param int|null $accessTimestamp
     */
    public function touchAccess(?int $accessTimestamp = null): void
    {
        touch($this->path, filemtime($this->path), $accessTimestamp ?? time());
    }

    /**
     * Opens the file in memory and returns the resource handle for manual manipulations of the file. Will need to be
     * manually closed outside of this class. The available modes are the one used with fopen.
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
