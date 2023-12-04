<?php namespace Zephyrus\Exceptions\Mailer;

class MailerAttachmentNotFoundException extends MailerException
{
    private string $path;

    public function __construct(string $path)
    {
        parent::__construct("The specified attachment path [$path] does not exist. Make sure the file is available on the filesystem before attaching to the Mailer.", 15004);
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
