<?php namespace Zephyrus\Exceptions\Mailer;

class MailerSmtpEncryptionException extends MailerException
{
    public function __construct()
    {
        parent::__construct("SMTP encryption configuration is invalid. Value must be either 'ssl' or 'tls'.", 15002);
    }
}
