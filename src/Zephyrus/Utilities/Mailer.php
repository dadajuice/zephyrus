<?php namespace Zephyrus\Utilities;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    private PHPMailer $phpMailer;
    private MailerSmtpConfiguration $configuration;
    private bool $useSmtp;

    public function __construct(MailerSmtpConfiguration $configuration)
    {
        $this->configuration = $configuration;
        $this->phpMailer = new PHPMailer();
        $this->phpMailer->CharSet = 'UTF-8';
        $this->initializeSmtp();
    }

    public function build(string $subject): Email
    {
        $instance = clone $this->phpMailer;
        $instance->clearAllRecipients();
        $instance->clearAttachments();
        return new Email($instance, $this->useSmtp, $subject);
    }

    public function getPhpMailer(): PHPMailer
    {
        return $this->phpMailer;
    }

    /**
     * Applies the FROM address and name for all emails built from this Mailer instance.
     *
     * @param string $email
     * @param string $name
     */
    public function setFrom(string $email, string $name): void
    {
        try {
            $this->phpMailer->setFrom($email, $name);
        } catch (Exception $e) {
            // TODO: Invalid Address ...
        }
    }

    private function initializeSmtp(): void
    {
        $this->useSmtp = $this->configuration->isEnabled();
        if ($this->useSmtp) {
            $this->phpMailer->isSMTP();
            $this->phpMailer->Host = $this->configuration->getHost();
            $this->phpMailer->SMTPAuth = $this->configuration->hasAuthentication();
            $this->phpMailer->Username = $this->configuration->getUsername();
            $this->phpMailer->Password = $this->configuration->getPassword();
            $this->phpMailer->SMTPSecure = $this->configuration->getEncryption();
            $this->phpMailer->Port = $this->configuration->getPort();
        }
    }
}
