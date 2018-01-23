<?php namespace Zephyrus\Application;

use Zephyrus\Security\EncryptedSessionHandler;
use Zephyrus\Security\SecuritySession;

class Session
{
    const DEFAULT_SESSION_NAME = 'PHPSESSID';

    /**
     * @var Session unique class instance (singleton)
     */
    private static $instance = null;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var \SessionHandler
     */
    private $handler = null;

    /**
     * @var array
     */
    private $configurations;

    /**
     * @var SecuritySession
     */
    private $security = null;

    /**
     * Obtain the single allowed instance for Session through singleton pattern
     * method.
     *
     * @return Session
     */
    final public static function getInstance(?array $configurations = null): Session
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($configurations);
        }
        return self::$instance;
    }

    final public static function kill()
    {
        self::$instance = null;
    }

    /**
     * @return string
     */
    final public static function getSavePath()
    {
        return (!empty(session_save_path())) ? session_save_path() : sys_get_temp_dir();
    }

    /**
     * Determines if the specified key exists in the current session. Optionally,
     * if the value argument is used, it also validates that the value is exactly
     * the one provided.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function has(string $key, $value = null): bool
    {
        return is_null($value)
            ? isset($_SESSION[$key])
            : isset($_SESSION[$key]) && $_SESSION[$key] == $value;
    }

    /**
     * Adds or modifies a session value by providing the desired key and
     * corresponding value.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Removes the session data associated with the provided key.
     * @param string $key
     */
    public function remove(string $key)
    {
        if (isset($_SESSION[$key])) {
            $_SESSION[$key] = '';
            unset($_SESSION[$key]);
        }
    }

    /**
     * Obtains the session value associated with the provided key. If the key
     * doesn't exists in the current session, the default value is thus
     * returned (which is null if not defined by the user).
     *
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    public function read(string $key, $defaultValue = null)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $defaultValue;
    }

    /**
     * Start session according to configuration. To manipulate session data,
     * use normal $_SESSION variable.
     */
    public function start()
    {
        session_name($this->name);
        session_set_save_handler(is_null($this->handler)
            ? new \SessionHandler()
            : $this->handler, true);
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->sessionId = session_id();
        if ($this->security->start()) {
            $this->refresh();
        }
    }

    /**
     * Properly delete the entire session (cookie expiration and session data).
     */
    public function destroy()
    {
        $_SESSION = [];
        setcookie(session_name(), '', 1);
        unset($_COOKIE[session_name()]);
        session_destroy();
    }

    /**
     * Regenerates a session identifier for the current user session and
     * deletes old session file.
     */
    public function refresh()
    {
        session_regenerate_id(true);
        $this->sessionId = session_id();
    }

    /**
     * Restart the entire session by regenerating the identifier and deleting
     * all data.
     */
    public function restart()
    {
        $this->destroy();
        $this->start();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->sessionId;
    }

    /**
     * Session constructor with default settings. Verify if default PHP
     * configuration uses cookie as transport mechanism. Otherwise throws
     * exception. Also make sure the cookie is always accessible through HTTP
     * only and automatically make it secure when HTTPS connection is used.
     *
     * @throws \Exception
     */
    private function __construct(?array $configurations = null)
    {
        if (!ini_get('session.use_cookies') || !ini_get('session.use_only_cookies')) {
            throw new \Exception("Session configurations are not secure.
            Fixation may be possible. Please review your php.ini or local
            settings (eg. .htaccess) for directive session.use_cookies and
            session.use_only_cookies.");
        }
        $this->configurations = $configurations ?? Configuration::getSessionConfiguration();
        $this->initialize();
    }

    /**
     * Initializes all data required from various setting to implement the
     * session class. Can be overwritten by children class to add new
     * features.
     */
    private function initialize()
    {
        $this->name = (isset($this->configurations['name']))
            ? $this->configurations['name']
            : self::DEFAULT_SESSION_NAME;
        $this->assignSessionLifetime();
        if (isset($this->configurations['encryption_enabled'])
            && $this->configurations['encryption_enabled']) {
            $this->handler = new EncryptedSessionHandler();
        }
        $this->security = new SecuritySession($this->configurations);
    }

    /**
     * Registers a different session lifetime if configured. Assigns the
     * gc_maxlifetime with a little more time to make sure the client cookie
     * expires before the server garbage collector.
     */
    private function assignSessionLifetime()
    {
        if (isset($this->configurations['lifetime'])) {
            $lifetime = $this->configurations['lifetime'];
            ini_set('session.gc_maxlifetime', $lifetime * 1.2);
            session_set_cookie_params($lifetime);
        }
    }
}
