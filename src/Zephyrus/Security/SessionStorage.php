<?php namespace Zephyrus\Security;

use Zephyrus\Application\SessionStorage as BaseSessionStorage;
use Zephyrus\Network\Request;
use Zephyrus\Security\Session\SessionDecoy;
use Zephyrus\Security\Session\SessionExpiration;
use Zephyrus\Security\Session\SessionFingerprint;

class SessionStorage extends BaseSessionStorage
{
    /**
     * @var SessionExpiration
     */
    private $expiration;

    /**
     * @var SessionDecoy
     */
    private $decoy;

    /**
     * @var SessionFingerprint
     */
    private $fingerprint;

    /**
     * @var bool defines if the stored session date should be encrypted
     */
    private $encryptionEnabled;

    public function __construct(array $config, Request $request)
    {
        parent::__construct($config['name'] ?? null);
        $this->encryptionEnabled = $config['encryption_enabled'] ?? false;
        $this->expiration = new SessionExpiration($config, $this);
        $this->fingerprint = new SessionFingerprint($config, $this, $request);
        $this->decoy = new SessionDecoy($config);
    }

    public function start()
    {
        if ($this->encryptionEnabled) {
            $this->setHandler(new EncryptedSessionHandler());
        }
        parent::start();
        $this->expiration->start();
        $this->fingerprint->start();

        if (!isset($_SESSION['__HANDLER_INITIATED'])) {
            $this->refresh();
            $this->decoy->throwDecoys();
            $_SESSION['__HANDLER_INITIATED'] = true;
        } else {
            if (!$this->fingerprint->hasValidFingerprint()) {
                throw new \Exception("Session fingerprint doesn't match");
            }
            if ($this->expiration->isObsolete()) {
                $this->refresh();
            }
        }
    }

    public function destroy()
    {
        parent::destroy();
        if (!is_null($this->decoy)) {
            $this->decoy->destroyDecoys();
        }
    }

    public function refresh()
    {
        parent::refresh();
        if (!is_null($this->expiration)) {
            $this->expiration->initiateExpiration();
        }
    }

    /**
     * @return bool
     */
    public function isEncryptionEnabled(): bool
    {
        return $this->encryptionEnabled;
    }

    /**
     * @return SessionExpiration
     */
    public function getExpiration(): SessionExpiration
    {
        return $this->expiration;
    }

    /**
     * @return SessionDecoy
     */
    public function getDecoy(): SessionDecoy
    {
        return $this->decoy;
    }

    /**
     * @return SessionFingerprint
     */
    public function getFingerprint(): SessionFingerprint
    {
        return $this->fingerprint;
    }
}
