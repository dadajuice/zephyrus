<?php namespace Zephyrus\Exceptions\Mailer;

class MailerInvalidAddressException extends MailerException
{
    private string $emailAddress;

    public function __construct(string $emailAddress)
    {
        parent::__construct("The email address [$emailAddress] is invalid.", 15003);
        $this->emailAddress = $emailAddress;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }
}
