<?php namespace Zephyrus\Security\Uploaders;

use Zephyrus\Exceptions\UploadException;

/**
 * REFERENCES
 * https://www.owasp.org/index.php/PHP_Security_Cheat_Sheet#File_uploads
 */
class FileUpload
{
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
     * @var bool Determines if an existing file in the destination directory
     * can be overwritten or throw an exception.
     */
    private $overwritePermitted = true;

    /**
     * @var int Uploaded file size in bytes
     */
    private $size;

    /**
     * @var string Uploaded file REAL mime type which should be used
     */
    private $mimeType;

    /**
     * @var string Uploaded file extension in lowercase
     */
    private $extension;

    /**
     * @var string Uploaded file temporary filename including complete path
     */
    private $temporaryFilename;

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
     * @var mixed[] Associative array defining the uploaded file basic $_FILES
     * characteristics (e.g. filename, error, size, ...).
     */
    private $rawData = null;

    /**
     * @var string Uploaded file name as defined in the user's disk including
     * the extension.
     */
    private $originalFilename;

    /**
     * @var string Uploaded file original file name without extension
     */
    private $originalBasename;

    /**
     * @var string Uploaded file mime type as provided by the client (browser)
     */
    private $originalMimeType;

    /**
     * Returns the maximum allowed upload size (in MB) based on the server
     * configurations. To increment this size the upload_max_filesize and
     * post_max_size properties must be modified (either directly in the
     * PHP.ini, in a .htaccess or using ini_set function).
     *
     * @return int
     */
    public static final function getServerMaxUploadSize()
    {
        $maxUpload = (int)(ini_get('upload_max_filesize'));
        $maxPost = (int)(ini_get('post_max_size'));
        $memoryLimit = (int)(ini_get('memory_limit'));
        return min($maxUpload, $maxPost, $memoryLimit);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static final function hasError($name)
    {
        return $_FILES[$name]['error'] > UPLOAD_ERR_OK;
    }

    /**
     * @param mixed[] $data
     * @throws UploadException
     * @throws \Exception
     */
    public function __construct($data)
    {
        $this->rawData = $data;
        if ($this->rawData['error'] > UPLOAD_ERR_OK) {
            throw new UploadException($this->rawData['error']);
        }
        $this->setMaxSize(self::getServerMaxUploadSize());
        $this->initialize();
    }

    /**
     * Upload the current file to the specified destination as the defined
     * filename. If the filename has not been previously set with
     * setDestinationFilename, user can set it directly with this method. Will
     * produce the same result. Returns the complete upload path on success and
     * will throw an exception otherwise.
     *
     * @param string $filename (optional)
     * @param bool $forceExtension (optional)
     * @return string
     * @throws \Exception
     */
    public final function upload($filename = null, $forceExtension = true)
    {
        if (!is_null($filename)) {
            $this->setDestinationFilename($filename, $forceExtension);
        }

        $this->initializeDefaultFilename();

        $this->validateUpload();
        $destination = $this->destinationDirectory . $this->destinationFilename;

        if (!$this->overwritePermitted && file_exists($destination)) {
            throw new \Exception("File ({$destination}) already exists and overwrite is not allowed");
        }

        if (!move_uploaded_file($this->temporaryFilename, $destination)) {
            throw new \Exception("Upload failed");
        }

        return $destination;
    }

    /**
     * Add an allowed extension for the current upload
     *
     * @param string $extension
     */
    public function addAllowedExtension($extension)
    {
        $this->allowedExtensions[] = $extension;
    }

    /**
     * Add a list of allowed extension for the current upload
     *
     * @param string[] $extensions
     */
    public function addAllowedExtensions($extensions)
    {
        if (!is_array($extensions)) {
            throw new \InvalidArgumentException("Specified argument must be an array");
        }
        $this->allowedExtensions = array_merge($this->allowedExtensions, $extensions);
    }

    /**
     * @return string[]
     */
    public function getAllowedExtensions()
    {
        return $this->allowedExtensions;
    }

    /**
     * Add an allowed mime type for the current upload
     *
     * @param string $mimeType
     */
    public function addAllowedMimeType($mimeType)
    {
        $this->allowedMimeTypes[] = $mimeType;
    }

    /**
     * Add a list of allowed mime types for the current upload
     *
     * @param string[] $mimeTypes
     */
    public function addAllowedMimeTypes($mimeTypes)
    {
        if (!is_array($mimeTypes)) {
            throw new \InvalidArgumentException("Specified argument must be an array");
        }
        $this->allowedMimeTypes = array_merge($this->allowedMimeTypes, $mimeTypes);
    }

    /**
     * @return string[]
     */
    public function getAllowedMimeTypes()
    {
        return $this->allowedMimeTypes;
    }

    /**
     * @return boolean
     */
    public function isOverwritePermitted()
    {
        return $this->overwritePermitted;
    }

    /**
     * @param boolean $overwritePermitted
     */
    public function setOverwritePermitted($overwritePermitted)
    {
        $this->overwritePermitted = $overwritePermitted;
    }

    /**
     * @return int
     */
    public function getMaxSize()
    {
        return $this->maxSize;
    }

    /**
     * @param int $maxSize
     */
    public function setMaxSize($maxSize)
    {
        $this->maxSize = $maxSize;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return (int)$this->size;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Apply the uploaded file directory to be used as a destination. Specified
     * path starts from the project root directory. Will trim unnecessary
     * leading slashes.
     *
     * @param string $destinationDirectory
     */
    public function setDestinationDirectory($destinationDirectory)
    {
        $destinationDirectory = ltrim($destinationDirectory, DIRECTORY_SEPARATOR);
        if ($destinationDirectory[strlen($destinationDirectory) - 1] != DIRECTORY_SEPARATOR) {
            $destinationDirectory .= DIRECTORY_SEPARATOR;
        }
        $this->destinationDirectory = ROOT_DIR . DIRECTORY_SEPARATOR . $destinationDirectory;
    }

    /**
     * @return string
     */
    public function getDestinationDirectory()
    {
        return $this->destinationDirectory;
    }

    /**
     * @return string
     */
    public function getTemporaryFilePath()
    {
        return $this->temporaryFilename;
    }

    /**
     * Apply the uploaded filename to be used in the destination directory. To
     * keep the same extension, simply omit to specify it in the filename and
     * if it is needed to explicitly upload a file with no extension, disable
     * the last argument (forceExtension).
     *
     * @param string $filename
     * @param bool $forceExtension (optional)
     */
    public function setDestinationFilename($filename, $forceExtension = true)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (empty($extension) && $forceExtension) {
            $filename .= '.' . $this->extension;
        }
        $this->destinationFilename = $filename;
    }

    /**
     * @return string
     */
    public function getDestinationFilename()
    {
        return $this->destinationFilename;
    }

    /**
     * @return string
     */
    public function getDestinationTarget()
    {
        return $this->destinationDirectory . $this->destinationFilename;
    }

    /**
     * Allows to keep the original filename (as specified by the uploader) as a
     * destination filename if no other has been provided. NOT RECOMMENDED.
     */
    public function keepOriginalFilename()
    {
        $this->randomizeFilename = false;
    }

    /**
     * @return string
     */
    public function getOriginalFilename()
    {
        return $this->originalFilename;
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
     * @return string
     */
    protected function getTemporaryFilename()
    {
        return $this->temporaryFilename;
    }

    /**
     * Initialize object from raw data obtain from $_FILES
     */
    private function initialize()
    {
        $info = pathinfo($this->rawData['name']);
        $this->originalFilename = $info['basename'];
        $this->originalBasename = $info['filename'];
        $this->originalMimeType = $this->rawData['type'];
        $this->temporaryFilename = $this->rawData['tmp_name'];
        $this->size = $this->rawData['size'];
        $this->mimeType = $this->getRealMimeType();
        $this->extension = strtolower($info['extension']);
        $this->destinationDirectory = ROOT_DIR;
        $this->destinationFilename = null;
    }

    /**
     * @return bool
     */
    private function hasValidSize()
    {
        return (($this->size / 1024 / 1024) <= $this->maxSize);
    }

    /**
     * @return bool
     */
    private function hasValidExtension()
    {
        if (!empty($this->allowedExtensions) && !in_array($this->extension, $this->allowedExtensions)) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    private function hasValidMimeType()
    {
        if (!empty($this->allowedMimeTypes) && !in_array($this->mimeType, $this->allowedMimeTypes)) {
            return false;
        }
        return true;
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
                : $this->originalFilename;
        }
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
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $this->temporaryFilename);
        finfo_close($finfo);
        return $mime;
    }

    /**
     * @return string
     */
    private function getRandomFilename()
    {
        return md5(uniqid(rand(0, time()), true)) . '.' . $this->extension;
    }
}