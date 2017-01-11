<?php namespace Zephyrus\Security;

use Zephyrus\Network\RequestFactory;

class SessionFingerprint
{
    /**
     * @var bool defines if the user agent should be considered when creating the fingerprint
     */
    private $userAgentFingerprinted;

    /**
     * @var bool defines if the ip address should be considered when creating the fingerprint
     */
    private $ipAddressFingerprinted;

    public function __construct(array $config)
    {
        $this->userAgentFingerprinted = $config['fingerprint_agent'] ?? false;
        $this->ipAddressFingerprinted = $config['fingerprint_ip'] ?? false;
    }

    public function start()
    {
        $this->setupFingerprintHandler();
    }

    /**
     * Determines if the stored fingerprint match the data sent with the
     * current HTTP request.
     *
     * @return bool
     */
    public function hasValidFingerprint()
    {
        if (isset($_SESSION['__HANDLER_FINGERPRINT'])) {
            return $_SESSION['__HANDLER_FINGERPRINT'] === $this->createFingerprint();
        }
        return true;
    }

    /**
     * @return boolean
     */
    public function isUserAgentFingerprinted(): bool
    {
        return $this->userAgentFingerprinted;
    }

    /**
     * @param boolean $fingerprinted
     * @throws \RuntimeException
     */
    public function setUserAgentFingerprinted(bool $fingerprinted)
    {
        $this->userAgentFingerprinted = $fingerprinted;
    }

    /**
     * @return boolean
     */
    public function isIpAddressFingerprinted(): bool
    {
        return $this->ipAddressFingerprinted;
    }

    /**
     * @param boolean $ipAddressFingerprinted
     * @throws \RuntimeException
     */
    public function setIpAddressFingerprinted(bool $ipAddressFingerprinted)
    {
        $this->ipAddressFingerprinted = $ipAddressFingerprinted;
    }

    /**
     * Creates an hashed fingerprint based on the defined session configuration
     * which might include client's ip address and/or client's user agent.
     *
     * @return string
     */
    private function createFingerprint()
    {
        $fingerprint = '';
        if ($this->ipAddressFingerprinted) {
            $fingerprint .= RequestFactory::create()->getClientIp();
        }
        if ($this->userAgentFingerprinted) {
            $fingerprint .= RequestFactory::create()->getUserAgent();
        }
        return hash('sha256', $fingerprint);
    }

    /**
     * Initiates session fingerprint based on user agent configurations and / or
     * ip address. Fingerprint which includes ip address might not work with
     * proxies based networks.
     */
    private function setupFingerprintHandler()
    {
        if (!$this->userAgentFingerprinted && !$this->ipAddressFingerprinted) {
            if (isset($_SESSION['__HANDLER_FINGERPRINT'])) {
                unset($_SESSION['__HANDLER_FINGERPRINT']);
            }
        } else {
            if (!isset($_SESSION['__HANDLER_FINGERPRINT'])) {
                $_SESSION['__HANDLER_FINGERPRINT'] = $this->createFingerprint();
            }
        }
    }
}