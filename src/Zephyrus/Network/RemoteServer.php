<?php namespace Zephyrus\Network;

class RemoteServer
{
    const DEFAULT_PING_TIMEOUT = 2;

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var string
     */
    private $ipAddress;

    public function __construct(string $hostname)
    {
        if (filter_var($hostname, FILTER_VALIDATE_IP)) {
            $this->ipAddress = $hostname;
            $this->hostname = null;
        } else {
            $this->hostname = $hostname;
            $this->ipAddress = gethostbyname($hostname);
        }
    }

    /**
     * @return string
     */
    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    /**
     * @return string
     */
    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    /**
     * @codeCoverageIgnore
     * @param int $type
     * @return array
     */
    public function getDnsRecord(int $type = DNS_ANY): array
    {
        return dns_get_record($this->ipAddress, $type);
    }

    /**
     * Tries to open a socket within the given timeout at the specified port.
     *
     * @param int $port
     * @param int $timeout
     * @return bool
     */
    public function isServiceAvailable(int $port = 80, int $timeout = self::DEFAULT_PING_TIMEOUT): bool
    {
        $isUp = false;
        if ($socket = @fsockopen($this->ipAddress, $port, $errno, $errstr, $timeout)) {
            $isUp = true;
            fclose($socket);
        }
        return $isUp;
    }

    /**
     * Retrieves the ISO date of the SSL certificate.
     *
     * @param int $port
     * @return string
     */
    public function getSslExpiration(int $port = 443): string
    {
        $results = shell_exec("echo | openssl s_client -connect {$this->ipAddress}:$port 2>/dev/null | openssl x509 -noout -dates");
        $parts = array_filter(explode(PHP_EOL, $results));
        $expiration = str_replace('notAfter=', '', $parts[1]);
        return date(FORMAT_DATE_TIME, strtotime($expiration));
    }
}
