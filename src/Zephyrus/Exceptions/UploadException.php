<?php

namespace Zephyrus\Exceptions;

class UploadException extends \Exception
{
    const ERR_FILE_SIZE = 900;
    const ERR_EXTENSION = 901;
    const ERR_MIME_TYPE = 902;
    const ERR_DIRECTORY_EXISTS = 903;
    const ERR_DIRECTORY_WRITABLE = 904;

    public function __construct($code)
    {
        parent::__construct($this->codeToMessage($code), $code);
    }

    // http://php.net/manual/en/features.file-upload.errors.php
    private function codeToMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = 'The uploaded file was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = 'No file was uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = 'Missing a temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = 'Failed to write file to disk';
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = 'File upload stopped by extension';
                break;
            case self::ERR_EXTENSION:
                $message = 'File extension is not allowed';
                break;
            case self::ERR_FILE_SIZE:
                $message = 'File is too large';
                break;
            case self::ERR_MIME_TYPE:
                $message = 'Mime type is not allowed';
                break;
            case self::ERR_DIRECTORY_EXISTS:
                $message = "Destination directory doesn't exists";
                break;
            case self::ERR_DIRECTORY_WRITABLE:
                $message = 'Destination directory is not writable';
                break;
            default:
                $message = 'Unknown upload error';
                break;
        }

        return $message;
    }
}
