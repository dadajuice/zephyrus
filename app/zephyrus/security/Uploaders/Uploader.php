<?php namespace Zephyrus\Security\Uploaders;

use Zephyrus\Exceptions\UploadException;

class Uploader
{
    /**
     * @var bool Determines if the upload should check for multiple files
     */
    private $multipleFiles = false;

    /**
     * @var mixed[] Associative array defining the uploaded file basic $_FILES
     * characteristics (e.g. filename, error, size, ...).
     */
    private $rawData = null;

    /**
     * @var FileUpload[]
     */
    private $uploadFiles = [];

    /**
     * @param string $name
     * @throws UploadException
     * @throws \Exception
     */
    public function __construct($name)
    {
        if (!isset($_FILES[$name])) {
            throw new \Exception("_FILES[$name] is not set. If you used a web form, maybe you forgot to set the enctype property.");
        }
        $this->rawData = $_FILES[$name];
        if (is_array($this->rawData['error'])) {
            $this->multipleFiles = true;
        }

        if ($this->multipleFiles) {
            $n = count($this->rawData['error']);
            for ($i = 0; $i < $n; ++$i) {
                if ($this->rawData['error'][$i] > UPLOAD_ERR_OK) {
                    throw new UploadException($this->rawData['error'][$i]);
                }
                $this->uploadFiles[] = new FileUpload([
                    'error' => $this->rawData['error'][$i],
                    'type' => $this->rawData['type'][$i],
                    'name' => $this->rawData['name'][$i],
                    'tmp_name' => $this->rawData['tmp_name'][$i],
                    'size' => $this->rawData['size'][$i]
                ]);
            }
        } else {
            if ($this->rawData['error'] > UPLOAD_ERR_OK) {
                throw new UploadException($this->rawData['error']);
            }
            $this->uploadFiles[] = new FileUpload($this->rawData);
        }
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->uploadFiles);
    }

    /**
     * @return FileUpload[]
     */
    public function getFiles()
    {
        return $this->uploadFiles;
    }
}