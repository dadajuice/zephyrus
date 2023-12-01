<?php namespace Zephyrus\Application\Mailer;

use Zephyrus\Exceptions\Mailer\MailerSmtpEncryptionException;
use Zephyrus\Exceptions\Mailer\MailerSmtpPortException;

class MailerSmtpConfiguration
{
    public const DEFAULT_CONFIGURATIONS = [
        'enabled' => false, // Enables the SMTP processing of emails, default to false which makes available the raw message.
        'host' => '', // SMTP server to send through (e.g. smtp.example.com)
        'port' => 465, // TCP port to connect to; use 587 if you have set encryption to 'tls'
        'encryption' => 'ssl', // Encryption algorithm to use (ssl | tls)
        'username' => '', // SMTP username
        'password' => '' // SMTP password
    ];

    private array $configurations;
    private bool $enabled;
    private string $host;
    private int $port;
    private string $encryption;
    private string $username;
    private string $password;

    /**
     * @throws MailerSmtpPortException
     * @throws MailerSmtpEncryptionException
     */
    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        $this->initializeConfigurations($configurations);
        $this->initializeEnabled();
        $this->initializeHost();
        $this->initializeAuthentication();
        $this->initializeEncryption();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function hasAuthentication(): bool
    {
        return !empty($this->password);
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getEncryption(): string
    {
        return $this->encryption;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    private function initializeConfigurations(array $configurations): void
    {
        $this->configurations = $configurations;
    }

    private function initializeEnabled(): void
    {
        $this->enabled = (bool) $this->configurations['enabled']
            ?? self::DEFAULT_CONFIGURATIONS['enabled'];
    }

    /**
     * @throws MailerSmtpEncryptionException
     */
    private function initializeEncryption(): void
    {
        $encryption = $this->configurations['encryption']
            ?? self::DEFAULT_CONFIGURATIONS['encryption'];
        if (!in_array($encryption, ['ssl', 'tls'])) {
            throw new MailerSmtpEncryptionException();
        }
        $this->encryption = $encryption;
    }

    /**
     * @throws MailerSmtpPortException
     */
    private function initializeHost(): void
    {
        $this->host = $this->configurations['host']
            ?? self::DEFAULT_CONFIGURATIONS['host'];
        $port = $this->configurations['port']
            ?? self::DEFAULT_CONFIGURATIONS['port'];
        if (!is_numeric($port)) {
            throw new MailerSmtpPortException();
        }
        $this->port = $port;
    }

    private function initializeAuthentication(): void
    {
        $this->username = $this->configurations['username']
            ?? self::DEFAULT_CONFIGURATIONS['username'];
        $this->password = $this->configurations['password']
            ?? self::DEFAULT_CONFIGURATIONS['password'];
    }
}
