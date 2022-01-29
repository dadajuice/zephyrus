<?php namespace Zephyrus\Utilities\Uploader;

use InvalidArgumentException;
use Zephyrus\Exceptions\UploaderException;
use Zephyrus\Network\ContentType;
use Zephyrus\Security\Cryptography;
use Zephyrus\Utilities\FileSystem\Directory;
use Zephyrus\Utilities\FileSystem\File;

class FileUpload
{
    /**
     * Original received data array for file upload. Should contain the following keys : ['error', 'tmp_name', 'type',
     * 'name', 'size'].
     *
     * @var array
     */
    private array $rawData;

    /**
     * File instance for the temporary uploaded file.
     *
     * @var File
     */
    private File $file;

    /**
     * Maximum file size in byte allowed per single uploaded file. Defaults to 0 which means it has no limit. In that
     * case the limit would be defined by the php.ini configurations.
     *
     * @var int
     */
    private int $maximumFileSize = 0;

    /**
     * List of extensions (without ".") allowed for the upload file. Defaults to anything.
     *
     * @var array
     */
    private array $allowedExtensions = [];

    /**
     * List of mime types allowed for the upload file. Defaults to any content type.
     *
     * @var array
     */
    private array $allowedMimeTypes = [ContentType::ANY];

    /**
     * Defines if the destination file can be overwritten if it already exists. Otherwise, it will throw an
     * UploadException.
     *
     * @var bool
     */
    private bool $overwritePermitted = false;

    /**
     * If the specified destination doesn't exist, this property determines if the class should try to create the
     * folders required. Throws an exception if it's not possible or if this setting is false and the destination
     * doesn't exist.
     *
     * @var bool
     */
    private bool $destinationCreationPermitted = true;

    /**
     * Defines if the object should rename the uploaded file to a secure cryptographic random new name or keep the
     * original one. Defaults to false for security measures (an uploader should not be able to guess the uploaded
     * file name).
     *
     * @var bool
     */
    private bool $keepOriginalName = false;

    /**
     * Defines a custom function to be called when a new filename needs to be generated.
     *
     * @var callable|null
     */
    private $customGenerationCallback = null;

    /**
     * Builds an instance based on a valid $_FILE element which is an array with the following keys : error, size,
     * tmp_name, name and size. Upon instanciation, if something is wrong with the given data, an exception will be
     * thrown preventing any instance to be built. Makes sure that the given element is properly formed and is really
     * an uploaded file.
     *
     * @param array $data
     * @throws UploaderException
     */
    public function __construct(array $data)
    {
        $this->initializeRawData($data);
        $this->initializeFile($data);
    }

    /**
     * Proceeds to move the file from the temporary uploaded folder to a specified destination. If no filename is given,
     * the instance will try to forge a filename and keep the original extension based on the current configurations. By
     * default, if no filename is given, a new cryptographic random filename will be generated (recommended). Throws an
     * exception if the validations failed or if something happened with the upload.
     *
     * @param string $destinationDirectory
     * @param string|null $filename
     * @return string
     * @throws UploaderException
     */
    public function upload(string $destinationDirectory, ?string $filename = null): string
    {
        $this->validateBeforeUpload();
        if (is_null($filename)) {
            $filename = ($this->keepOriginalName)
                ? $this->getOriginalFilename()
                : $this->generateNewFilename();
        }
        $path = $this->prepareDestination($destinationDirectory, $filename);
        if (!$this->moveUploadedFile($this->getTemporaryFilepath(), $path)) {
            throw new UploaderException(UploaderException::ERROR_MOVE_UPLOADED_FILE_FAILED, $this->rawData);
        }
        return $path;
    }

    /**
     * Retrieves the original filename used for the uploaded file. It should be the original name of the file from the
     * client computer. Will be used by default as the destination name if the keepOriginalName property is true and no
     * other filename is given.
     *
     * @return string
     */
    public function getOriginalFilename(): string
    {
        return $this->rawData['name'];
    }

    /**
     * Retrieves the extension of the uploaded file based on the original name. Cannot use the REAL file representation
     * since it can be altered by web server / PHP to be stored in tmp folder with unique name which may skip the
     * extension. Be warned that someone could potentially inject something through the extension if it's not properly
     * validated.
     *
     * @return string
     */
    public function getExtension(): string
    {
        return pathinfo($this->getOriginalFilename(), PATHINFO_EXTENSION);
    }

    /**
     * Retrieves the uploaded file size in bytes. Uses the REAL file size and doesn't consider the given size when the
     * file was uploaded as this could be forged.
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->file->size();
    }

    /**
     * Retrieves the uploaded file mime type. Uses the REAL mime type and doesn't consider the given mime type when the
     * file was uploaded as this could be forged.
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->file->getMimeType();
    }

    /**
     * Retrieves the uploaded file temporary path before being uploaded. This value doesn't make sense once the upload
     * has been made.
     *
     * @return string
     */
    public function getTemporaryFilepath(): string
    {
        return $this->rawData['tmp_name'];
    }

    /**
     * Verifies all restrictions.
     *
     * @return bool
     */
    public function verify(): bool
    {
        return $this->isSizeAllowed() && $this->isMimeTypeAllowed() && $this->isExtensionAllowed();
    }

    /**
     * Verifies if the file's mime type is allowed for the upload session.
     *
     * @return bool
     */
    public function isMimeTypeAllowed(): bool
    {
        if (empty($this->allowedMimeTypes)
            || in_array(ContentType::ANY, $this->allowedMimeTypes)
            || in_array($this->file->getMimeType(), $this->allowedMimeTypes)) {
            return true;
        }
        return false;
    }

    /**
     * Verifies if the file's extension is allowed for the upload session.
     *
     * @return bool
     */
    public function isExtensionAllowed(): bool
    {
        if (empty($this->allowedExtensions)) {
            return true;
        }
        return in_array(strtolower($this->getExtension()), $this->allowedExtensions);
    }

    /**
     * Verifies if the file size is below or equal the defined upload size limit.
     *
     * @return bool
     */
    public function isSizeAllowed(): bool
    {
        if ($this->maximumFileSize <= 0) {
            return true;
        }
        return ($this->file->size() <= $this->maximumFileSize);
    }

    /**
     * Applies a restriction over uploaded file extension. By default, any extension is allowed. User should verify the
     * mime types instead as its more reliable and precise.
     *
     * @param array $extensions
     */
    public function setAllowedExtensions(array $extensions)
    {
        foreach ($extensions as &$extension) {
            $extension = strtolower(ltrim($extension, "."));
        }
        $this->allowedExtensions = $extensions;
    }

    /**
     * Applies a restriction over uploaded file mime type. By default, any mime type is allowed.
     *
     * @param array $mimeTypes
     */
    public function setAllowedMimeTypes(array $mimeTypes)
    {
        foreach ($mimeTypes as &$mimeType) {
            $mimeType = strtolower($mimeType);
        }
        $this->allowedMimeTypes = $mimeTypes;
    }

    /**
     * Defines the maximum allowed size for the file in bytes.
     *
     * @param int $bytes
     */
    public function setAllowedSize(int $bytes)
    {
        if ($bytes < 0) {
            throw new InvalidArgumentException("Allowed size must be positive int value.");
        }
        $this->maximumFileSize = $bytes;
    }

    /**
     * Applies if the class should overwrite the destination file if it exists. Defaults to false.
     *
     * @param bool $permitOverwrite
     */
    public function setOverwritePermitted(bool $permitOverwrite)
    {
        $this->overwritePermitted = $permitOverwrite;
    }

    /**
     * Applies if the class should attempt to create the destination folder in case it doesn't exist. Defaults to true.
     * Otherwise, an exception would occur.
     *
     * @param bool $permitDestinationCreation
     */
    public function setDestinationCreationPermitted(bool $permitDestinationCreation)
    {
        $this->destinationCreationPermitted = $permitDestinationCreation;
    }

    /**
     * Determines if the uploader should keep the original name or generate a random one when no filename is specified
     * in the upload.
     *
     * @param bool $keepOriginalName
     */
    public function setKeepOriginalName(bool $keepOriginalName)
    {
        $this->keepOriginalName = $keepOriginalName;
    }

    /**
     * Changes the default random filename generator function (cryptographic random of 24 characters). Given callback
     * must return a string.
     *
     * @param callable $callback
     */
    public function setCustomFilenameGenerator(callable $callback)
    {
        $this->customGenerationCallback = $callback;
    }

    /**
     * Simple wrapper method for move_uploaded_file allowing easier mock data for unit testing. Normal class usage
     * should not override this method.
     *
     * @param string $temporaryFilepath
     * @param string $destinationFilepath
     * @return bool
     */
    protected function moveUploadedFile(string $temporaryFilepath, string $destinationFilepath): bool
    {
        return move_uploaded_file($temporaryFilepath, $destinationFilepath);
    }

    /**
     * Simple wrapper method for is_uploaded_file allowing easier mock data for unit testing. Normal class usage should
     * not override this method.
     *
     * @param string $temporaryFilepath
     * @return bool
     */
    protected function isUploadedFile(string $temporaryFilepath): bool
    {
        return is_uploaded_file($temporaryFilepath);
    }

    /**
     * Launches the verifications before proceeding with the upload. It should not be possible to call the upload
     * method on a file which is not compliant with the configured restrictions.
     *
     * @throws UploaderException
     */
    private function validateBeforeUpload()
    {
        if (!$this->isExtensionAllowed()) {
            throw new UploaderException(UploaderException::ERROR_UPLOAD_EXTENSION, $this->rawData);
        }
        if (!$this->isMimeTypeAllowed()) {
            throw new UploaderException(UploaderException::ERROR_UPLOAD_MIME_TYPE, $this->rawData);
        }
        if (!$this->isSizeAllowed()) {
            throw new UploaderException(UploaderException::ERROR_UPLOAD_SIZE, $this->rawData);
        }
    }

    /**
     * Prepares and validates the given form upload raw data. Verifies if the structure is compliant with a valid upload
     * and makes sure the error key for any of the uploaded file is valid.
     *
     * @param array $rawData
     * @throws UploaderException
     */
    private function initializeRawData(array $rawData)
    {
        $this->verifyRawDataStructure($rawData);
        $this->verifyRawDataError($rawData);
        $this->rawData = $rawData;
    }

    /**
     * @param array $data
     * @throws UploaderException
     */
    private function verifyRawDataStructure(array $data)
    {
        $neededKeys = ['error', 'tmp_name', 'type', 'name', 'size'];
        $missingKeys = array_diff_key(array_flip($neededKeys), $data);
        if (!empty($missingKeys)) {
            throw new UploaderException(UploaderException::ERROR_INVALID_STRUCTURE, $data);
        }
        if (!is_int($data['error'])) {
            throw new UploaderException(UploaderException::ERROR_INVALID_STRUCTURE, $data);
        }
    }

    /**
     * @param array $data
     * @throws UploaderException
     */
    private function verifyRawDataError(array $data)
    {
        if ($data['error'] > UPLOAD_ERR_OK) {
            throw new UploaderException($data['error'], $data);
        }
    }

    /**
     * @param array $data
     * @throws UploaderException
     */
    private function initializeFile(array $data)
    {
        if (!File::exists($data['tmp_name'])) {
            throw new UploaderException(UploaderException::ERROR_UNREADABLE_TMP_FILE, $data);
        }
        if (!$this->isUploadedFile($data['tmp_name'])) {
            throw new UploaderException(UploaderException::ERROR_NOT_UPLOADED_FILE, $data);
        }
        $this->file = new File($data['tmp_name']);
    }

    /**
     * Executes the filename generation custom callback if defined. Otherwise, returns a cryptographic random string of
     * 24 characters.
     *
     * @return string
     */
    private function generateNewFilename(): string
    {
        return (!is_null($this->customGenerationCallback))
            ? ($this->customGenerationCallback)()
            : $this->defaultFilenameGenerator();
    }

    private function defaultFilenameGenerator(): string
    {
        $extension = $this->getExtension();
        $basename = Cryptography::randomString(24);
        if (!empty($extension)) {
            $basename .= '.' . $extension;
        }
        return $basename;
    }

    /**
     * @param string $destinationDirectory
     * @param string $filename
     * @return string
     * @throws UploaderException
     */
    private function prepareDestination(string $destinationDirectory, string $filename): string
    {
        $destinationDirectory = rtrim($destinationDirectory, " \t\n\r\0\x0B" . DIRECTORY_SEPARATOR);
        if (!Directory::exists($destinationDirectory)) {
            if (!$this->destinationCreationPermitted) {
                throw new UploaderException(UploaderException::ERROR_INVALID_DESTINATION, $this->rawData);
            }
            Directory::create($destinationDirectory);
        }
        if (File::exists($destinationDirectory . DIRECTORY_SEPARATOR . $filename) && !$this->overwritePermitted) {
            throw new UploaderException(UploaderException::ERROR_DESTINATION_ALREADY_EXISTS, $this->rawData);
        }
        return $destinationDirectory . DIRECTORY_SEPARATOR . $filename;
    }
}
