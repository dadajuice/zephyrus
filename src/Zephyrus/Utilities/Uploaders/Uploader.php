<?php namespace Zephyrus\Utilities\Uploaders;

use Zephyrus\Exceptions\UploadException;
use Zephyrus\Network\RequestFactory;

class Uploader
{
    const TYPE_FILE = 0;
    const TYPE_IMAGE = 1;
    const TYPE_AUTO = 2;

    /**
     * @var bool Determines if the upload should check for multiple files
     */
    private $multipleFiles = false;

    /**
     * @var mixed[] Associative array defining the uploaded file basic
     * characteristics (e.g. filename, error, size, ...).
     */
    private $rawData = null;

    /**
     * @var FileUploader[]
     */
    private $uploadFiles = [];

    /**
     * @var int
     */
    private $fileType = self::TYPE_AUTO;

    /**
     * @param string $name
     * @throws UploadException
     * @throws \Exception
     */
    public function __construct($name)
    {
        $this->initializeRawData($name);
        if (is_array($this->rawData['error'])) {
            $this->multipleFiles = true;
            $this->initializeMultipleFiles();
        } else {
            $this->initializeSingleFile();
        }
    }

    /**
     * @return int
     */
    public function getFileType(): int
    {
        return $this->fileType;
    }

    /**
     * @param int $fileType
     */
    public function setFileType(int $fileType)
    {
        $this->fileType = $fileType;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->uploadFiles);
    }

    /**
     * @return FileUploader[]
     */
    public function getFiles(): array
    {
        return $this->uploadFiles;
    }

    private function initializeRawData(string $name)
    {
        $file = RequestFactory::read()->getFile($name);
        if (is_null($file)) {
            throw new \Exception("_FILES[$name] is not set. If you used a web form, maybe you forgot to set the enctype property.");
        }
        $this->rawData = $file;
    }

    private function initializeMultipleFiles()
    {
        for ($i = 0; $i < count($this->rawData['error']); ++$i) {
            if ($this->rawData['error'][$i] > UPLOAD_ERR_OK) {
                throw new UploadException($this->rawData['error'][$i]);
            }
            $file = new UploadFile([
                'error' => $this->rawData['error'][$i],
                'type' => $this->rawData['type'][$i],
                'name' => $this->rawData['name'][$i],
                'tmp_name' => $this->rawData['tmp_name'][$i],
                'size' => $this->rawData['size'][$i]
            ]);
            $this->uploadFiles[] = ($this->isImage($this->rawData['type'][$i]))
                ? new ImageUploader($file)
                : new FileUploader($file);
        }
    }

    private function initializeSingleFile()
    {
        if ($this->rawData['error'] > UPLOAD_ERR_OK) {
            throw new UploadException($this->rawData['error']);
        }
        $file = new UploadFile($this->rawData);
        $this->uploadFiles[] = ($this->isImage($this->rawData['type']))
            ? new ImageUploader($file)
            : new FileUploader($file);
    }

    private function isImage($mimeType): bool
    {
        return in_array($mimeType, ImageUploader::PERMITTED_MIME_TYPES);
    }
}
