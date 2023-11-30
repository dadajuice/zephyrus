<?php namespace Zephyrus\Utilities;

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
        $enabled = $this->configurations['enabled'] ?? self::DEFAULT_CONFIGURATIONS['enabled'];
        if (!is_bool($enabled)) {
            //throw new SessionLifetimeException();
            //TODO: throw
        }
        $this->enabled = $enabled;
    }

    private function initializeEncryption(): void
    {
        $encryption = $this->configurations['encryption'] ?? self::DEFAULT_CONFIGURATIONS['encryption'];
        if (!in_array($encryption, ['ssl', 'tls'])) {
            //throw new SessionLifetimeException();
            //TODO: throw
        }
        $this->encryption = $encryption;
    }

    private function initializeHost(): void
    {
        $this->host = $this->configurations['host'] ?? self::DEFAULT_CONFIGURATIONS['host'];
        $port = $this->configurations['port'] ?? self::DEFAULT_CONFIGURATIONS['port'];
        if (!is_numeric($port)) {
            //throw new SessionLifetimeException();
            //TODO: throw
        }
        $this->port = $port;
    }

    private function initializeAuthentication(): void
    {
        $this->username = $this->configurations['username'] ?? self::DEFAULT_CONFIGURATIONS['username'];
        $this->password = $this->configurations['password'] ?? self::DEFAULT_CONFIGURATIONS['password'];
    }
}
