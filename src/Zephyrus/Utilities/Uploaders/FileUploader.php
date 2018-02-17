<?php namespace Zephyrus\Utilities\Uploaders;

use Zephyrus\Exceptions\UploadException;

/**
 * REFERENCES
 * https://www.owasp.org/index.php/PHP_Security_Cheat_Sheet#File_uploads.
 */
class FileUploader
{
    /**
     * @var UploadFile
     */
    protected $uploadFile;

    /**
     * @var string[] List of all allowed extension (ex. gif) for the current
     * upload. If empty any extension is considered as valid.
     */
    private $allowedExtensions = [];

    /**
     * @var string[] List of all allowed mime types (e.g. image/gif) for the
     * current upload. If empty any mime type is considered as valid.
     */
    private $allowedMimeTypes = [];

    /**
     * @var int Maximum allowed size for the current upload in megabytes
     */
    private $maxSize = 2;

    /**
     * @var string Uploaded file destination path (default : project root folder)
     */
    private $destinationDirectory;

    /**
     * @var string Uploaded file destination name including extension
     */
    private $destinationFilename;

    /**
     * @var bool Determines if the destination file should be randomly
     * calculated while uploading if no filename has been provided.
     */
    private $randomizeFilename = true;

    /**
     * @var bool Determines if an existing file in the destination directory
     * can be overwritten or throw an exception.
     */
    private $overwritePermitted = true;

    public function __construct(UploadFile $uploadFile)
    {
        $this->uploadFile = $uploadFile;
    }

    final public function upload($filename = null): string
    {
        if (!is_null($filename)) {
            $this->setDestinationFilename($filename);
        }
        $this->initializeDefaultFilename();
        $destination = $this->getDestinationTarget();
        if (!$this->overwritePermitted && file_exists($destination)) {
            throw new \Exception("File ({$destination}) already exists and overwrite is not allowed");
        }
        $this->validateUpload();
        $this->uploadFile->upload($destination);
        return $destination; // @codeCoverageIgnore
    }

    /**
     * Add an allowed extension for the current upload.
     *
     * @param string $extension
     */
    final public function addAllowedExtension(string $extension)
    {
        $this->allowedExtensions[] = $extension;
    }

    /**
     * Add a list of allowed extension for the current upload.
     *
     * @param string[] $extensions
     */
    final public function addAllowedExtensions(array $extensions)
    {
        $this->allowedExtensions = array_merge($this->allowedExtensions, $extensions);
    }

    /**
     * @return string[]
     */
    final public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }

    /**
     * Add an allowed mime type for the current upload.
     *
     * @param string $mimeType
     */
    final public function addAllowedMimeType(string $mimeType)
    {
        $this->allowedMimeTypes[] = $mimeType;
    }

    /**
     * Add a list of allowed mime types for the current upload.
     *
     * @param string[] $mimeTypes
     */
    final public function addAllowedMimeTypes(array $mimeTypes)
    {
        $this->allowedMimeTypes = array_merge($this->allowedMimeTypes, $mimeTypes);
    }

    /**
     * @return string[]
     */
    final public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * @return int
     */
    final public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    /**
     * @param int $maxSize
     */
    final public function setMaxSize(int $maxSize)
    {
        $this->maxSize = $maxSize;
    }

    /**
     * @return bool
     */
    final public function isOverwritePermitted(): bool
    {
        return $this->overwritePermitted;
    }

    /**
     * @param bool $overwritePermitted
     */
    final public function setOverwritePermitted(bool $overwritePermitted)
    {
        $this->overwritePermitted = $overwritePermitted;
    }

    /**
     * Apply the uploaded file directory to be used as a destination. Specified
     * path starts from the project root directory. Will trim unnecessary
     * leading slashes.
     *
     * @param string $destinationDirectory
     */
    final public function setDestinationDirectory(string $destinationDirectory)
    {
        if ($destinationDirectory[strlen($destinationDirectory) - 1] != DIRECTORY_SEPARATOR) {
            $destinationDirectory .= DIRECTORY_SEPARATOR;
        }
        $this->destinationDirectory = $destinationDirectory;
    }

    /**
     * @return string
     */
    final public function getDestinationDirectory(): string
    {
        return $this->destinationDirectory;
    }

    /**
     * Apply the uploaded filename to be used in the destination directory. To
     * keep the same extension, simply omit to specify it in the filename.
     *
     * @param string $filename
     */
    final public function setDestinationFilename(string $filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (empty($extension) && !empty($this->uploadFile->getExtension())) {
            $filename .= '.' . $this->uploadFile->getExtension();
        }
        $this->destinationFilename = $filename;
    }

    /**
     * @return string
     */
    final public function getDestinationFilename(): string
    {
        return $this->destinationFilename;
    }

    /**
     * @return string
     */
    final public function getDestinationTarget(): string
    {
        return $this->destinationDirectory . $this->destinationFilename;
    }

    /**
     * Allows to keep the original filename (as specified by the uploader) as a
     * destination filename if no other has been provided. NOT RECOMMENDED.
     *
     * @param bool $keep
     */
    final public function setKeepOriginalFilename(bool $keep)
    {
        $this->randomizeFilename = !$keep;
    }

    /**
     * Do various validations to assure that the upload will be possible. Check
     * for file size, extension, mime type and destination directory validity
     * and accessibility. Inherited classes must override this method for
     * specific validation operations (so not forget to include
     * parent::validateUpload() to keep default processing).
     *
     * @throws UploadException
     */
    protected function validateUpload()
    {
        if (!$this->hasValidSize()) {
            throw new UploadException(UploadException::ERR_FILE_SIZE);
        }
        if (!$this->hasValidExtension()) {
            throw new UploadException(UploadException::ERR_EXTENSION);
        }
        if (!$this->hasValidMimeType()) {
            throw new UploadException(UploadException::ERR_MIME_TYPE);
        }
        if (!is_dir($this->destinationDirectory)) {
            throw new UploadException(UploadException::ERR_DIRECTORY_EXISTS);
        }
        if (!is_writable($this->destinationDirectory)) {
            throw new UploadException(UploadException::ERR_DIRECTORY_WRITABLE);
        }
    }

    /**
     * @return bool
     */
    private function hasValidSize(): bool
    {
        return ($this->uploadFile->getSize() / 1024 / 1024) <= $this->maxSize;
    }

    /**
     * @return bool
     */
    private function hasValidExtension(): bool
    {
        return empty($this->allowedExtensions) ||
            in_array($this->uploadFile->getExtension(), $this->allowedExtensions);
    }

    /**
     * @return bool
     */
    private function hasValidMimeType(): bool
    {
        return empty($this->allowedMimeTypes)
            || in_array($this->uploadFile->getMimeType(), $this->allowedMimeTypes);
    }

    /**
     * Apply destination filename either to a random string or the original
     * filename specified by the uploader (not recommended).
     */
    private function initializeDefaultFilename()
    {
        if (is_null($this->destinationFilename)) {
            $this->destinationFilename = ($this->randomizeFilename)
                ? $this->getRandomFilename()
                : $this->uploadFile->getOriginalFilename();
        }
    }

    /**
     * @return string
     */
    private function getRandomFilename(): string
    {
        $file = md5(uniqid(rand(0, time()), true));
        $extension = (!empty($this->extension)) ? '.' . $this->extension : '';
        return $file . $extension;
    }
}
