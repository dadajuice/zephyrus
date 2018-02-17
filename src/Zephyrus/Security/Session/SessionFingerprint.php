<?php namespace Zephyrus\Security\Session;

use Zephyrus\Network\Request;
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

    /**
     * @var Request
     */
    private $request;

    public function __construct(bool $userAgentFingerprinted, bool $ipAddressFingerprinted)
    {
        $this->request = RequestFactory::read();
        $this->userAgentFingerprinted = $userAgentFingerprinted;
        $this->ipAddressFingerprinted = $ipAddressFingerprinted;
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
        return isset($_SESSION['__HANDLER_FINGERPRINT'])
            && $_SESSION['__HANDLER_FINGERPRINT'] === $this->createFingerprint();
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
            $fingerprint .= $this->request->getClientIp();
        }
        if ($this->userAgentFingerprinted) {
            $fingerprint .= $this->request->getUserAgent();
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
        if ($this->userAgentFingerprinted || $this->ipAddressFingerprinted
            && !isset($_SESSION['__HANDLER_FINGERPRINT'])) {
            $_SESSION['__HANDLER_FINGERPRINT'] = $this->createFingerprint();
        }
    }
}
