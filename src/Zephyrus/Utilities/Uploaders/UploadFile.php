<?php

namespace Zephyrus\Utilities\Uploaders;

use Zephyrus\Exceptions\UploadException;

class UploadFile
{
    /**
     * @var string
     */
    private $temporaryFilename;

    /**
     * @var string
     */
    private $originalBasename;

    /**
     * @var string
     */
    private $originalFilename;

    /**
     * @var string
     */
    private $mimeType;

    /**
     * @var string
     */
    private $extension;

    /**
     * @var int File size in bytes
     */
    private $size;

    /**
     * @var mixed[] Associative array defining the uploaded file basic
     *              characteristics (e.g. filename, error, size, ...).
     */
    private $rawData;

    public function __construct(array $data)
    {
        $this->initializeRawData($data);
        if ($this->rawData['error'] > UPLOAD_ERR_OK) {
            throw new UploadException($this->rawData['error']);
        }
        $this->initialize();
    }

    /**
     * Upload the current file to the specified destination as the defined
     * filename. If the filename has not been previously set with
     * setDestinationFilename, user can set it directly with this method. Will
     * produce the same result. Returns the complete upload path on success and
     * will throw an exception otherwise.
     *
     * @param string $destination
     *
     * @throws \Exception
     */
    final public function upload(string $destination)
    {
        if (!move_uploaded_file($this->temporaryFilename, $destination)) {
            throw new \Exception('Upload failed');
        }
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @return string
     */
    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    /**
     * @return string
     */
    public function getTemporaryFilename(): string
    {
        return $this->temporaryFilename;
    }

    /**
     * @param array $data
     *
     * @throws \InvalidArgumentException
     */
    private function initializeRawData(array $data)
    {
        $keys = ['error', 'tmp_name', 'type', 'name', 'size'];
        $missingKeys = array_diff_key(array_flip($keys), $data);
        if (!empty($missingKeys)) {
            throw new \InvalidArgumentException('Argument must be a valid file data [' .
                print_r($missingKeys, true) . ' missing]');
        }
        $this->rawData = $data;
    }

    private function initialize()
    {
        $info = pathinfo($this->rawData['name']);
        $this->temporaryFilename = $this->rawData['tmp_name'];
        $this->originalFilename = $info['basename'];
        $this->originalBasename = $info['filename'];
        $this->mimeType = $this->getRealMimeType();
        $this->extension = strtolower($info['extension']);
        $this->size = max(filesize($this->rawData['tmp_name']), $this->rawData['size']);
    }

    /**
     * Obtain the concrete mime type of uploaded file based signature. This
     * mime type should be used for security check instead of the one provided
     * in the HTTP request which could be spoofed.
     *
     * @return string
     */
    private function getRealMimeType()
    {
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($info, $this->temporaryFilename);
        finfo_close($info);

        return $mime;
    }
}
