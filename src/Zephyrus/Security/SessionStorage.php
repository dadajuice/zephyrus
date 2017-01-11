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
        $this->expiration = new SessionExpiration($config);
        $this->decoy = new SessionDecoy($config);
        $this->fingerprint = new SessionFingerprint($config);
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
        $this->expiration->initiateExpiration();
    }

    /**
     * @return bool
     */
    public function isEncryptionEnabled(): bool
    {
        return $this->encryptionEnabled;
    }

    /**
     * @param bool $encryptionEnabled
     */
    public function setEncryptionEnabled(bool $encryptionEnabled)
    {
        $this->encryptionEnabled = $encryptionEnabled;
    }
}
