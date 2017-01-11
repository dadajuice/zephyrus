<?php namespace Zephyrus\Security;

use Zephyrus\Application\SessionStorage as BaseSessionStorage;

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

    public function __construct(array $config)
    {
        parent::__construct($config['name'] ?? null);
        $this->encryptionEnabled = $config['encryption_enabled'] ?? false;
    }

    public function start()
    {
        if ($this->encryptionEnabled) {
            $this->setHandler(new EncryptedSessionHandler());
        }
        parent::start();
        if (!is_null($this->expiration)) {
            $this->expiration->start();
        }
        if (!is_null($this->fingerprint)) {
            $this->fingerprint->start();
        }

        if (!isset($_SESSION['__HANDLER_INITIATED'])) {
            $this->refresh();
            if (!is_null($this->decoy)) {
                $this->decoy->throwDecoys();
            }
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
     * @param SessionExpiration $expiration
     */
    public function setExpiration(SessionExpiration $expiration)
    {
        $this->expiration = $expiration;
    }

    /**
     * @return SessionDecoy
     */
    public function getDecoy(): SessionDecoy
    {
        return $this->decoy;
    }

    /**
     * @param SessionDecoy $decoy
     */
    public function setDecoy(SessionDecoy $decoy)
    {
        $this->decoy = $decoy;
    }

    /**
     * @return SessionFingerprint
     */
    public function getFingerprint(): SessionFingerprint
    {
        return $this->fingerprint;
    }

    /**
     * @param SessionFingerprint $fingerprint
     */
    public function setFingerprint(SessionFingerprint $fingerprint)
    {
        $this->fingerprint = $fingerprint;
    }
}
