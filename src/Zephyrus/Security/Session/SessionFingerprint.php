<?php namespace Zephyrus\Security\Session;

use Zephyrus\Application\SessionStorage;
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

    /**
     * @var array
     */
    protected $content = [];

    public function __construct(array $config, SessionStorage $storage)
    {
        $this->content = &$storage->getContent();
        $this->request = RequestFactory::read();
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
        if (isset($this->content['__HANDLER_FINGERPRINT'])) {
            return $this->content['__HANDLER_FINGERPRINT'] === $this->createFingerprint();
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
     * @param boolean $fingerprinted
     * @throws \RuntimeException
     */
    public function setIpAddressFingerprinted(bool $fingerprinted)
    {
        $this->ipAddressFingerprinted = $fingerprinted;
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
        if (!$this->userAgentFingerprinted && !$this->ipAddressFingerprinted) {
            if (isset($this->content['__HANDLER_FINGERPRINT'])) {
                unset($this->content['__HANDLER_FINGERPRINT']);
            }
        } else {
            if (!isset($this->content['__HANDLER_FINGERPRINT'])) {
                $this->content['__HANDLER_FINGERPRINT'] = $this->createFingerprint();
            }
        }
    }
}
