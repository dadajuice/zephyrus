<?php namespace Zephyrus\Exceptions;

class HttpUploadException extends \Exception
{
    public const ERROR_INVALID_CHUNK = 901;
    public const ERROR_NO_CHUNK_UPLOADED = 902;
    public const ERROR_EMPTY_CHUNK_UPLOADED = 903;
    public const ERROR_SIZE_OVERFLOW = 904;
    public const ERROR_ALREADY_STARTED = 905;
    public const ERROR_NO_LONGER_AVAILABLE = 906;
    public const ERROR_INVALID_UPLOAD = 907;
    public const ERROR_DESTINATION_NOT_WRITABLE = 908;

    public static function invalidChunk(): self
    {
        return new self(self::ERROR_INVALID_CHUNK);
    }

    public static function invalidUpload(): self
    {
        return new self(self::ERROR_INVALID_UPLOAD);
    }

    public static function noChunkUploaded(): self
    {
        return new self(self::ERROR_NO_CHUNK_UPLOADED);
    }

    public static function emptyChunkUploaded(): self
    {
        return new self(self::ERROR_EMPTY_CHUNK_UPLOADED);
    }

    public static function sizeOverflow(): self
    {
        return new self(self::ERROR_SIZE_OVERFLOW);
    }

    public static function alreadyStarted(): self
    {
        return new self(self::ERROR_ALREADY_STARTED);
    }

    public static function noLongerAvailable(): self
    {
        return new self(self::ERROR_NO_LONGER_AVAILABLE);
    }

    public static function directoryNotWritable(): self
    {
        return new self(self::ERROR_DESTINATION_NOT_WRITABLE);
    }

    public function __construct(int $code)
    {
        parent::__construct($this->codeToMessage($code), $code);
    }

    private function codeToMessage(int $code): string
    {
        return match ($code) {
            self::ERROR_INVALID_CHUNK => "Invalid chunk data structure received. The received data must include the following parameters 'upload_uuid', 'upload_chunk', 'upload_total_chunks', 'upload_total_size'.",
            self::ERROR_NO_CHUNK_UPLOADED => "No chunk uploaded. The received data must include the parameter 'upload_file' which contains the data as a proper file upload.",
            self::ERROR_EMPTY_CHUNK_UPLOADED => "The uploaded chunk appears to be empty (no byte uploaded).",
            self::ERROR_SIZE_OVERFLOW => "Trying to upload a chunk past the original file length.",
            self::ERROR_ALREADY_STARTED => "File upload has already been started for given uuid.",
            self::ERROR_NO_LONGER_AVAILABLE => "Previous chunks are no longer available or wasn't properly uploaded. Please restart the upload session.",
            self::ERROR_INVALID_UPLOAD => "Invalid uploaded data structure. The received data must include the parameter 'upload_file' which contains the data as a proper file upload.",
            self::ERROR_DESTINATION_NOT_WRITABLE => "The upload destination directory is not writable.",
            default => "Unknown HTTP upload error.",
        };
    }
}
