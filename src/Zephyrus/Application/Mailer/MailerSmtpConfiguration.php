<?php namespace Zephyrus\Application\Mailer;

use Zephyrus\Exceptions\Mailer\MailerSmtpEncryptionException;
use Zephyrus\Exceptions\Mailer\MailerSmtpPortException;

class MailerSmtpConfiguration
{
    public const DEFAULT_CONFIGURATIONS = [
        'enabled' => false, // Enables the SMTP processing of emails, default to false which makes available the raw message.
        'host' => '', // SMTP server to send through (e.g. smtp.example.com)
        'port' => 465, // TCP port to connect to; use 587 if you have set encryption to 'tls'
        'encryption' => 'ssl', // Encryption algorithm to use (none | ssl | tls)
        'username' => '', // SMTP username
        'password' => '', // SMTP password
        'debug' => false, // Use setting SMTPDebug=2 of PHPMailer for verbose debugging
        'allow_self_signed' => true, // SSL configuration to allow self signed
        'verify_peer' => false // SSL configuration to ignore peer verification
    ];

    private array $configurations;
    private bool $enabled;
    private string $host;
    private int $port;
    private string $encryption;
    private string $username;
    private string $password;
    private bool $debug;
    private array $sslOptions;

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
        $this->initializeDebug();
        $this->initializeSslOptions();
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

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function getSslOptions(): array
    {
        return $this->sslOptions;
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
        if (!in_array($encryption, ['none', 'ssl', 'tls'])) {
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

    private function initializeDebug(): void
    {
        $this->debug = (bool) $this->configurations['debug']
            ?? self::DEFAULT_CONFIGURATIONS['debug'];
    }

    private function initializeSslOptions(): void
    {
        $this->sslOptions = [
            'ssl' => [
                'verify_peer' => (bool) $this->configurations['verify_peer']
                    ?? self::DEFAULT_CONFIGURATIONS['verify_peer'],
                'verify_peer_name' => (bool) $this->configurations['verify_peer']
                    ?? self::DEFAULT_CONFIGURATIONS['verify_peer'],
                'allow_self_signed' => (bool) $this->configurations['allow_self_signed']
                    ?? self::DEFAULT_CONFIGURATIONS['allow_self_signed'],
            ]
        ];
    }
}
