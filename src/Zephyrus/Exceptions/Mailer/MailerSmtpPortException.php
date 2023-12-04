<?php namespace Zephyrus\Exceptions\Mailer;

class MailerSmtpPortException extends MailerException
{
    public function __construct()
    {
        parent::__construct("SMTP port configuration is invalid. Value must be numeric.", 15001);
    }
}
