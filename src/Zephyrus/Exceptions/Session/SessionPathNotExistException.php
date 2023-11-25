<?php namespace Zephyrus\Exceptions\Session;

class SessionPathNotExistException extends SessionException
{
    private string $savePath;

    public function __construct(string $savePath)
    {
        $this->savePath = $savePath;
        parent::__construct("The specified session save path [$savePath] doesn't exist.", 13004);
    }

    public function getSavePath(): string
    {
        return $this->savePath;
    }
}
