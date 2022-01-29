<?php namespace Zephyrus\Exceptions;

use Exception;

class UploaderException extends Exception
{
    public const ERROR_INVALID_STRUCTURE = 901;
    public const ERROR_INVALID_MULTIPLE_STRUCTURE = 902;
    public const ERROR_UNREADABLE_TMP_FILE = 903;
    public const ERROR_INVALID_DESTINATION = 904;
    public const ERROR_DESTINATION_ALREADY_EXISTS = 905;
    public const ERROR_NOT_UPLOADED_FILE = 906;
    public const ERROR_MOVE_UPLOADED_FILE_FAILED = 907;
    public const ERROR_UPLOAD_EXTENSION = 908;
    public const ERROR_UPLOAD_MIME_TYPE = 909;
    public const ERROR_UPLOAD_SIZE = 910;

    /**
     * List of the submitted form upload raw data that triggered the Exception.
     *
     * @var array
     */
    private array $rawData;

    public function __construct(int $code, array $rawData)
    {
        $this->rawData = $rawData;
        parent::__construct($this->codeToMessage($code), $code);
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    private function codeToMessage(int $code): string
    {
        return match ($code) {
            self::ERROR_UPLOAD_SIZE => "The uploaded file exceeds the size allowed.",
            self::ERROR_UPLOAD_EXTENSION => "The uploaded file extension is not allowed.",
            self::ERROR_UPLOAD_MIME_TYPE => "The uploaded file mime type is not allowed.",
            self::ERROR_INVALID_STRUCTURE => "The specified data has missing required key(s). An upload should have the following keys: error, tmp_name, type, name and size.",
            self::ERROR_INVALID_MULTIPLE_STRUCTURE => "For a multiple file upload, all data keys (error, tmp_name, type, name and size) should be an array of equal size matching the quantity of uploaded files.",
            self::ERROR_UNREADABLE_TMP_FILE => "The uploaded tmp file is not accessible or readable.",
            self::ERROR_INVALID_DESTINATION => "The destination doesn't exist and cannot be created.",
            self::ERROR_DESTINATION_ALREADY_EXISTS => "The destination file already exists and cannot be overwritten.",
            self::ERROR_NOT_UPLOADED_FILE => "The specified file wasn't uploaded.",
            self::ERROR_MOVE_UPLOADED_FILE_FAILED => "The temporary upload file couldn't be moved to destination.",
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded.",
            UPLOAD_ERR_NO_FILE => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION => "File upload stopped by extension.",
            default => "Unknown upload error.",
        };
    }
}
