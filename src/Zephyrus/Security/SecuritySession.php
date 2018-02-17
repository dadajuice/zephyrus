<?php namespace Zephyrus\Security;

use Zephyrus\Application\Session;
use Zephyrus\Security\Session\SessionDecoy;
use Zephyrus\Security\Session\SessionExpiration;
use Zephyrus\Security\Session\SessionFingerprint;

class SecuritySession
{
    /**
     * @var SessionExpiration
     */
    private $expiration = null;

    /**
     * @var SessionDecoy
     */
    private $decoy = null;

    /**
     * @var SessionFingerprint
     */
    private $fingerprint = null;

    public function __construct(array $configurations)
    {
        $fingerprintAgentEnabled = $configurations['fingerprint_agent'] ?? false;
        $fingerprintAddressEnabled = $configurations['fingerprint_ip'] ?? false;
        $decoys = $configurations['decoys'] ?? false;
        $refreshAfterNthRequests = $configurations['refresh_after_requests'] ?? false;
        $refreshAfterInterval = $configurations['refresh_after_interval'] ?? false;
        $refreshProbability = $configurations['refresh_probability'] ?? false;
        if ($fingerprintAgentEnabled || $fingerprintAddressEnabled) {
            $this->fingerprint = new SessionFingerprint($fingerprintAgentEnabled, $fingerprintAddressEnabled);
        }
        if ($decoys) {
            $this->decoy = new SessionDecoy($decoys);
        }
        if ($refreshAfterNthRequests || $refreshAfterInterval || $refreshProbability) {
            $this->expiration = new SessionExpiration($refreshAfterNthRequests, $refreshAfterInterval, $refreshProbability);
        }
    }

    /**
     * Throws an exception if the fingerprint mismatch.
     *
     * @throws \Exception
     */
    public function start(): bool
    {
        if (!is_null($this->expiration)) {
            $this->expiration->start();
        }
        if (!is_null($this->fingerprint)) {
            $this->fingerprint->start();
        }
        if (!isset($_SESSION['__HANDLER_INITIATED'])) {
            return $this->initialSessionStart();
        } else {
            return $this->laterSessionStart();
        }
    }

    public function destroy()
    {
        if (!is_null($this->decoy)) {
            $this->decoy->destroyDecoys();
        }
    }

    /**
     * Launches the first session start process which initialize the decoys if
     * configured and register __HANDLER_INITIATED session key.
     */
    private function initialSessionStart()
    {
        if (!is_null($this->decoy)) {
            $this->decoy->throwDecoys();
        }
        $_SESSION['__HANDLER_INITIATED'] = true;
        return false;
    }

    /**
     * Should be used for every session starts if the handlers has been
     * previously initiated. Validates if the session identifier should be
     * regenerated and validates current fingerprint.
     *
     * @throws \Exception
     */
    private function laterSessionStart()
    {
        if (!is_null($this->fingerprint) && !$this->fingerprint->hasValidFingerprint()) {
            throw new \Exception("Session fingerprint doesn't match"); // @codeCoverageIgnore
        }
        if (!is_null($this->expiration) && $this->expiration->isObsolete()) {
            return true;
        }
        return false;
    }
}
