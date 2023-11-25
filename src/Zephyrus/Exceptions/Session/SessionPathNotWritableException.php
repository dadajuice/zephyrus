<?php namespace Zephyrus\Exceptions\Session;

class SessionPathNotWritableException extends SessionException
{
    private string $savePath;

    public function __construct(string $savePath)
    {
        $this->savePath = $savePath;
        parent::__construct("The specified session save path [$savePath] is not writable.", 13005);
    }

    public function getSavePath(): string
    {
        return $this->savePath;
    }
}
