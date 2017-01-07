<?php namespace Zephyrus\Exceptions;

class ImageException extends \Exception
{
    const ERR_UNSUPPORTED_POSITION = 900;
    const ERR_FILE = 901;
    const ERR_FORMAT = 902;
    const ERR_CORRUPT = 903;
    const ERR_DIRECTORY_EXISTS = 904;
    const ERR_DIRECTORY_WRITABLE = 905;
    const ERR_OVERWRITE = 906;

    /**
     * @var string
     */
    private $path;

    public function __construct($code, $path = null)
    {
        $this->code = $code;
        $this->path = $path;
        $this->message = $this->buildMessage();
    }

    public function getPath()
    {
        return $this->path;
    }

    private function buildMessage()
    {
        switch ($this->code) {
            case self::ERR_FORMAT:
                return "Image format [$this->path] not supported.";
            case self::ERR_FILE:
                return "The specified file [$this->path] is not valid or is not readable.";
            case self::ERR_CORRUPT:
                return "An unexpected error occured while loading [$this->path]. Possible image corruption.";
            case self::ERR_DIRECTORY_EXISTS:
                return "The specified destination directory [$this->path] doesn't exists.";
            case self::ERR_DIRECTORY_WRITABLE:
                return "The specified destination directory [$this->path] is not writable.";
            case self::ERR_OVERWRITE:
                return "File [$this->path] already exists and overwrite is not allowed";
            case self::ERR_UNSUPPORTED_POSITION:
                return "The specified watermark position is not supported.";
        }
    }
}