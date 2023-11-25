<?php namespace Zephyrus\Core\Session;

use Zephyrus\Network\Request;

class SessionFingerprintManager
{
    private const SESSION_KEY = '__ZF_SESSION_FINGERPRINT';
    /**
     * Defines if the user agent should be considered when creating the fingerprint. Meaning the browser identification
     * should not change during the session.
     *
     * @var bool
     */
    private bool $userAgentFingerprinted;

    /**
     * Defines if the ip address should be considered when creating the fingerprint.  Meaning the user ip address should
     * not change during the session. Use carefully as this could provoke false positive (vpn, proxies, cellular
     * networks, etc.).
     *
     * @var bool
     */
    private bool $ipAddressFingerprinted;

    public function __construct(bool $userAgentFingerprinted, bool $ipAddressFingerprinted)
    {
        $this->userAgentFingerprinted = $userAgentFingerprinted;
        $this->ipAddressFingerprinted = $ipAddressFingerprinted;
    }

    /**
     * Start the identification process for the session. Consider that if the configurations changed during a client
     * active session, it would consider it invalid and thus require the client to reconnect. Initiates session
     * fingerprint based on user agent configurations and / or ip address. Fingerprint which includes ip address might
     * not work with proxies based networks.
     */
    public function start(?Request $request): void
    {
        if (($this->userAgentFingerprinted || $this->ipAddressFingerprinted)
            && !is_null($request)
            && !isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = $this->createFingerprint($request);
        }
    }

    /**
     * Checks if the session fingerprint data has been initiated.
     *
     * @return bool
     */
    public function isInitiated(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * Checks if the user agent (browser identification) of the client is currently fingerprinted for the session.
     *
     * @return bool
     */
    public function isUserAgentFingerprinted(): bool
    {
        return $this->userAgentFingerprinted;
    }

    /**
     * Checks if the ip address of the client is currently fingerprinted for the session.
     *
     * @return bool
     */
    public function isIpAddressFingerprinted(): bool
    {
        return $this->ipAddressFingerprinted;
    }

    /**
     * Determines if the stored fingerprint match the data sent with the current HTTP request.
     *
     * @param ?Request $request
     * @return bool
     */
    public function isValid(?Request $request): bool
    {
        return isset($_SESSION[self::SESSION_KEY])
            && !is_null($request)
            && $_SESSION[self::SESSION_KEY] === $this->createFingerprint($request);
    }

    /**
     * Creates a hashed fingerprint based on the defined session configuration which might include client's ip address
     * and/or client's user agent.
     *
     * @param Request $request
     * @return string
     */
    private function createFingerprint(Request $request): string
    {
        $fingerprint = '';
        if ($this->ipAddressFingerprinted) {
            $fingerprint .= $request->getClientIp();
        }
        if ($this->userAgentFingerprinted) {
            $fingerprint .= $request->getUserAgent();
        }
        return hash('sha256', $fingerprint);
    }
}
