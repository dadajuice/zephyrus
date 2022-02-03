<?php namespace Zephyrus\Application;

use SessionHandler;
use Zephyrus\Application\Session\EncryptedSessionHandler;
use Zephyrus\Application\Session\SessionExpiration;
use Zephyrus\Application\Session\SessionFingerprint;
use Zephyrus\Exceptions\SessionException;
use Zephyrus\Network\Cookie;
use Zephyrus\Utilities\FileSystem\Directory;

class Session
{
    public const DEFAULT_SESSION_NAME = 'phpsessid';
    public const DEFAULT_SAVE_PATH = ROOT_DIR . '/temp/sessions';
    public const DEFAULT_CONFIGURATIONS = [
        'enabled' => true, // Determines if the session should be used or not (e.g. API)
        'name' => self::DEFAULT_SESSION_NAME, // Name of the session cookie (empty means system default session_name())
        'lifetime' => Cookie::DURATION_SESSION, // Seconds before the session is considered to be expired
        'lifetime_mode' => 'default', // default (lifetime is global) or reset (each request refreshes the lifetime)
        'save_path' => '', // Path where to save the session files (empty means php.ini defaults)
        'encryption_enabled' => true, // Session files on the web server are encrypted
        'fingerprint_ip' => false, // Session cookie is bound to the client ip address
        'fingerprint_ua' => true, // Session cookie is bound to the client user agent
        'refresh_mode' => SessionExpiration::MODE_NONE, // none|probability|interval|request
        'refresh_rate' => 0 // for probability (0-100), interval (nb of seconds), request (nb of requests)
    ];

    /**
     * @var Session|null
     */
    private static ?Session $instance = null;

    /**
     * Loaded configurations for the session.
     *
     * @var array
     */
    private array $configurations;

    /**
     * @var SessionExpiration
     */
    private SessionExpiration $expiration;

    /**
     * @var SessionFingerprint
     */
    private SessionFingerprint $fingerprint;

    /**
     * Determines if the session is active.
     *
     * @var bool
     */
    private bool $enabled;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var int
     */
    private int $lifetime;

    /**
     * @var string
     */
    private string $lifetimeMode;

    /**
     * @var string
     */
    private string $savePath;

    /**
     * @var bool
     */
    private bool $encryptionEnabled;

    /**
     * @return string
     */
    final public static function getSystemSavePath(): string
    {
        return (!empty(session_save_path())) ? session_save_path() : sys_get_temp_dir();
    }

    final public static function getInstance(array $configurations = []): self
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
     * Session constructor with default settings. Verify if default PHP configuration uses cookie as transport
     * mechanism. Otherwise, throws an exception. Also make sure the cookie is always accessible through HTTP only and
     * automatically make it secure when HTTPS connection is used.
     *
     * @param array $configurations
     * @throws SessionException
     */
    public function __construct(array $configurations = [])
    {
        if (!ini_get('session.use_cookies') || !ini_get('session.use_only_cookies')) {
            throw new SessionException(SessionException::ERROR_UNSECURE_CONFIGURATIONS);
        }
        $this->initializeConfigurations($configurations);
        $this->initializeName();
        $this->initializeLifetime();
        $this->initializeLifetimeMode();
        $this->initializeSavePath();
        $this->initializeSecurity();
        $this->initializeRefresher();
    }

    /**
     * Start session according to configuration including fingerprinting and refresh. To manipulate session data, use
     * normal $_SESSION variable or use this class methods.
     *
     * @throws SessionException
     */
    public function start()
    {
        if (!$this->enabled) {
            return;
        }
        if (!$this->isStarted()) {
            $this->configure();
            session_start();
            if ($this->lifetimeMode == 'reset') {
                $cookie = new Cookie($this->getName(), $this->getId());
                $cookie->setLifetime($this->lifetime);
                $cookie->send();
            }
            $this->expiration->start();
            $this->fingerprint->start();
        }
        if ($this->expiration->isObsolete()) {
            $this->refresh();
        }
        if ($this->fingerprint->isInitiated()
            && !$this->fingerprint->hasValidFingerprint()) {
            throw new SessionException(SessionException::ERROR_INVALID_FINGERPRINT);
        }
    }

    /**
     * Properly delete the entire session including the cookie expiration and all saved session data.
     */
    public function destroy()
    {
        $_SESSION = [];
        setcookie(session_name(), '', 1);
        unset($_COOKIE[session_name()]);
        @session_destroy();
    }

    /**
     * Regenerates a session identifier for the current user session and deletes old session file. Useful to prevent
     * some form of session hijacking. Works automatically with the session refresher configurations (refresh_mode and
     * refresh_rate).
     */
    public function refresh()
    {
        session_regenerate_id(true);
    }

    /**
     * Restart the entire session by regenerating the identifier and deleting all data.
     *
     * @throws SessionException
     */
    public function restart()
    {
        $this->destroy();
        $this->start();
    }

    /**
     * Obtains the session value associated with the provided key. If the key doesn't exist in the current session, the
     * default value is thus returned (which is null if not defined by the user).
     *
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    public function read(string $key, mixed $defaultValue = null): mixed
    {
        return $_SESSION[$key] ?? $defaultValue;
    }

    /**
     * Adds or modifies a session value by providing the desired key and corresponding value.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, mixed $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Adds of modifies multiple session values at once.
     *
     * @param array $values
     */
    public function setAll(array $values)
    {
        foreach ($values as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }

    public function isStarted(): bool
    {
        return session_status() == PHP_SESSION_ACTIVE;
    }

    /**
     * Determines if the specified key exists in the current session. Optionally, if the value argument is used, it also
     * validates that the value is exactly the one provided.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function has(string $key, mixed $value = null): bool
    {
        return is_null($value)
            ? isset($_SESSION[$key])
            : isset($_SESSION[$key]) && $_SESSION[$key] == $value;
    }

    /**
     * Removes the session data associated with the provided key.
     *
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
     * Retrieves the current session identifier.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->isStarted() ? session_id() : null;
    }

    /**
     * Retrieves the current session name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->isStarted() ? session_name() : $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isEncryptionEnabled(): bool
    {
        return $this->encryptionEnabled;
    }

    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    public function getLifetimeMode(): string
    {
        return $this->lifetimeMode;
    }

    public function getSavePath(): string
    {
        return $this->savePath;
    }

    public function isUserAgentFingerprinted(): bool
    {
        return $this->fingerprint->isUserAgentFingerprinted();
    }

    public function isIpAddressFingerprinted(): bool
    {
        return $this->fingerprint->isIpAddressFingerprinted();
    }

    public function getRefreshMode(): string
    {
        return $this->expiration->getRefreshMode();
    }

    public function getRefreshRate(): int
    {
        return $this->expiration->getRefreshRate();
    }

    private function configure()
    {
        ini_set('session.gc_maxlifetime', $this->lifetime);
        ini_set('session.gc_probability', 1); // Debian usage
        ini_set('session.gc_divisor', 100);
        session_save_path($this->savePath);
        session_set_cookie_params([
            'lifetime' => $this->lifetime,
            'secure' => $_SERVER['HTTPS'] ?? false,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_name($this->name);
        session_set_save_handler($this->encryptionEnabled
            ? new EncryptedSessionHandler()
            : new SessionHandler(), true);
    }

    private function initializeConfigurations(array $configurations)
    {
        if (empty($configurations)) {
            $configurations = Configuration::getConfiguration('session') ?? self::DEFAULT_CONFIGURATIONS;
        }
        $this->configurations = $configurations;
        $this->enabled = (bool) ($this->configurations['enabled'] ?? self::DEFAULT_CONFIGURATIONS['enabled']);
    }

    private function initializeName()
    {
        $name = $this->configurations['name'] ?? self::DEFAULT_CONFIGURATIONS['name'];
        if (empty($name)) {
            $name = session_name();
        }
        $this->name = $name;
    }

    /**
     * @throws SessionException
     */
    private function initializeLifetime()
    {
        $lifetime = $this->configurations['lifetime'] ?? self::DEFAULT_CONFIGURATIONS['lifetime'];
        if (!is_numeric($lifetime)) {
            throw new SessionException(SessionException::ERROR_INVALID_LIFETIME);
        }
        $this->lifetime = (int) $lifetime;
    }

    /**
     * @throws SessionException
     */
    private function initializeLifetimeMode()
    {
        $lifetimeMode = $this->configurations['lifetime_mode'] ?? self::DEFAULT_CONFIGURATIONS['lifetime_mode'];
        if (!in_array($lifetimeMode, ['default', 'reset'])) {
            throw new SessionException(SessionException::ERROR_INVALID_LIFETIME_MODE);
        }
        $this->lifetimeMode = $lifetimeMode;
    }

    /**
     * @throws SessionException
     */
    private function initializeSavePath()
    {
        $savePath = $this->configurations['save_path'] ?? self::DEFAULT_CONFIGURATIONS['save_path'];
        if (empty($savePath)) {
            $savePath = self::getSystemSavePath();
        }
        if (!Directory::exists($savePath)) {
            throw new SessionException(SessionException::ERROR_SAVE_PATH_NOT_EXIST);
        }
        if (!Directory::isWritable($savePath)) {
            throw new SessionException(SessionException::ERROR_SAVE_PATH_NOT_WRITABLE);
        }
        $this->savePath = $savePath;
    }

    private function initializeSecurity()
    {
        $this->encryptionEnabled = (bool) ($this->configurations['encryption_enabled'] ?? self::DEFAULT_CONFIGURATIONS['encryption_enabled']);
        $fingerprintIp = (bool) ($this->configurations['fingerprint_ip'] ?? self::DEFAULT_CONFIGURATIONS['fingerprint_ip']);
        $fingerprintUserAgent = (bool) ($this->configurations['fingerprint_ua'] ?? self::DEFAULT_CONFIGURATIONS['fingerprint_ua']);
        $this->fingerprint = new SessionFingerprint($fingerprintUserAgent, $fingerprintIp);
    }

    /**
     * @throws SessionException
     */
    private function initializeRefresher()
    {
        $refreshMode = $this->configurations['refresh_mode'] ?? self::DEFAULT_CONFIGURATIONS['refresh_mode'];
        $refreshRate = $this->configurations['refresh_rate'] ?? self::DEFAULT_CONFIGURATIONS['refresh_rate'];
        if (!is_int($refreshRate)) {
            throw new SessionException(SessionException::ERROR_INVALID_REFRESH_RARE);
        }
        $this->expiration = new SessionExpiration($refreshMode, $refreshRate);
    }
}
