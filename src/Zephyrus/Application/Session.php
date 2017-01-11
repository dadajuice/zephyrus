<?php namespace Zephyrus\Application;

use Zephyrus\Security\Cryptography;
use Zephyrus\Security\EncryptedSessionHandler;

class Session
{
    /**
     * @var Session unique class instance (singleton)
     */
    private static $instance = null;

    /**
     * @var SessionStorage
     */
    private $sessionStorage;

    /**
     * Obtain the single allowed instance for Session through singleton pattern
     * method.
     *
     * @return Session
     */
    public static function getInstance(): Session
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Start session according to configuration. To manipulate session data,
     * use normal $_SESSION variable. Throws exception if fingerprint doesn't
     * match.
     *
     * @throws \Exception
     */
    public function start()
    {
        $this->sessionStorage->start();
    }

    public function destroy()
    {
        $this->sessionStorage->destroy();
    }

    public function refresh()
    {
        $this->sessionStorage->refresh();
    }

    /**
     * Restart the entire session by regenerating the identifier, deleting all
     * data and initiating handlers.
     */
    public function restart()
    {
        $this->sessionStorage->restart();
    }

    public function setSessionStorage(SessionStorage $sessionStorage)
    {
        $this->sessionStorage = $sessionStorage;
    }

    /**
     * Session constructor with default settings. Verify if default PHP
     * configuration uses cookie as transport mechanism. Otherwise throws
     * exception. Also make sure the cookie is always accessible through HTTP
     * only and automatically make it secure when HTTPS connection is used.
     *
     * @throws \Exception
     */
    private function __construct()
    {
        if (!ini_get('session.use_cookies') || !ini_get('session.use_only_cookies')) {
            throw new \Exception("Session configurations are not secure.
            Fixation may be possible. Please review your php.ini or local
            settings (eg. .htaccess) for directive session.use_cookies and
            session.use_only_cookies.");
        }
    }
}
