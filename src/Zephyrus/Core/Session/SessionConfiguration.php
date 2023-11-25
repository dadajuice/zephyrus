<?php namespace Zephyrus\Core\Session;

use SessionHandlerInterface;
use Zephyrus\Core\Session\Handlers\DatabaseSessionHandler;
use Zephyrus\Core\Session\Handlers\DefaultSessionHandler;
use Zephyrus\Core\Session\Handlers\EncryptedDatabaseSessionHandler;
use Zephyrus\Core\Session\Handlers\EncryptedDefaultSessionHandler;
use Zephyrus\Database\DatabaseSession;
use Zephyrus\Exceptions\Session\SessionDatabaseStructureException;
use Zephyrus\Exceptions\Session\SessionDatabaseTableException;
use Zephyrus\Exceptions\Session\SessionLifetimeException;
use Zephyrus\Exceptions\Session\SessionPathNotExistException;
use Zephyrus\Exceptions\Session\SessionPathNotWritableException;
use Zephyrus\Exceptions\Session\SessionRefreshRateException;
use Zephyrus\Exceptions\Session\SessionRefreshRateProbabilityException;
use Zephyrus\Exceptions\Session\SessionStorageModeException;
use Zephyrus\Exceptions\Session\SessionSupportedRefreshModeException;

class SessionConfiguration
{
    public const DEFAULT_DATABASE_TABLE = 'public.session';
    public const DEFAULT_LIFETIME = 1440; // 24 minutes

    public const DEFAULT_CONFIGURATIONS = [
        'name' => '', // Name of the session cookie (empty means php.ini defaults)
        'lifetime' => self::DEFAULT_LIFETIME, // Seconds before the session is considered to be expired (defaults to 24 mins)
        'encrypted' => false,
        'storage' => 'file', // Type of internal storage handler to use (file | database)
        'save_path' => '', // Specific for storage type 'file'. Path where to save the session files (empty means php.ini defaults)
        'table' => self::DEFAULT_DATABASE_TABLE, // Specific for storage type 'database'. Schema and table name where the session should be stored (needs session_id, access and data columns)
        'fingerprint_ip' => false,
        'fingerprint_ua' => false,
        'refresh_mode' => 'none', // Algorithm to use to automatically refresh the sess id (none|probability|interval|request)
        'refresh_rate' => 0 // For probability (0-100), interval (nb of seconds), request (nb of requests)
    ];

    private array $configurations;
    private string $name;
    private int $lifetime;
    private SessionHandlerInterface $sessionHandler;

    /**
     * @param array $configurations
     * @throws SessionDatabaseStructureException
     * @throws SessionDatabaseTableException
     * @throws SessionLifetimeException
     * @throws SessionPathNotExistException
     * @throws SessionPathNotWritableException
     * @throws SessionStorageModeException
     */
    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        $this->initializeConfigurations($configurations);
        $this->initializeName();
        $this->initializeLifetime();
        $this->initializeHandler();
    }

    public function configure(): void
    {
        $this->configureLifeTime();
        $this->configureCookie();
        $this->configureHandler();
    }

    public function buildFingerprint(): SessionFingerprintManager
    {
        $fingerprintIp = $this->configurations['fingerprint_ip']
            ?? self::DEFAULT_CONFIGURATIONS['fingerprint_ip'];
        $fingerprintUa = $this->configurations['fingerprint_ua']
            ?? self::DEFAULT_CONFIGURATIONS['fingerprint_ua'];
        return new SessionFingerprintManager($fingerprintUa, $fingerprintIp);
    }

    /**
     * @throws SessionRefreshRateException
     * @throws SessionSupportedRefreshModeException
     * @throws SessionRefreshRateProbabilityException
     */
    public function buildIdentifier(): SessionIdentifierManager
    {
        $refreshMode = $this->configurations['refresh_mode']
            ?? self::DEFAULT_CONFIGURATIONS['refresh_mode'];
        $refreshRate = $this->configurations['refresh_rate']
            ?? self::DEFAULT_CONFIGURATIONS['refresh_rate'];
        if (!in_array($refreshMode, ['none', 'probability', 'interval', 'request'])) {
            throw new SessionSupportedRefreshModeException($refreshMode);
        }
        if (!is_int($refreshRate) || $refreshRate < 0) {
            throw new SessionRefreshRateException();
        }
        if ($refreshMode == SessionIdentifierManager::MODE_PROBABILITY && $refreshRate > 100) {
            throw new SessionRefreshRateProbabilityException();
        }
        return new SessionIdentifierManager($refreshMode, $refreshRate);
    }

    public function getSavePath(): ?string
    {
        $mode = $this->configurations['storage'] ?? self::DEFAULT_CONFIGURATIONS['storage'];
        if ($mode == 'file') {
            return session_save_path();
        }
        return null;
    }

    private function configureHandler(): void
    {
        session_set_save_handler($this->sessionHandler, true);
    }

    private function configureLifeTime(): void
    {
        ini_set('session.gc_maxlifetime', $this->lifetime);
        ini_set('session.gc_probability', 1); // Debian usage
        ini_set('session.gc_divisor', 100);
    }

    private function configureCookie(): void
    {
        session_set_cookie_params([
            'lifetime' => $this->lifetime,
            'secure' => $_SERVER['HTTPS'] ?? false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_name($this->name);
    }

    private function initializeConfigurations(array $configurations): void
    {
        $this->configurations = $configurations;
    }

    private function initializeName(): void
    {
        $this->name = $this->configurations['name'] ?? self::DEFAULT_CONFIGURATIONS['name'];
        if (empty($this->name)) {
            $this->name = ini_get("session.name");
        }
    }

    /**
     * @throws SessionLifetimeException
     */
    private function initializeLifetime(): void
    {
        $lifetime = $this->configurations['lifetime'] ?? self::DEFAULT_CONFIGURATIONS['lifetime'];
        if (!is_numeric($lifetime)) {
            throw new SessionLifetimeException();
        }
        $this->lifetime = $lifetime;
    }

    /**
     * @throws SessionDatabaseStructureException
     * @throws SessionDatabaseTableException
     * @throws SessionPathNotExistException
     * @throws SessionPathNotWritableException
     * @throws SessionStorageModeException
     */
    private function initializeHandler(): void
    {
        $encrypted = $this->configurations['encrypted']
            ?? self::DEFAULT_CONFIGURATIONS['encrypted'];
        $mode = $this->configurations['storage']
            ?? self::DEFAULT_CONFIGURATIONS['storage'];
        if (!in_array($mode, ['file', 'database'])) {
            throw new SessionStorageModeException();
        }
        match ($mode) {
            'file' => self::initializeFileHandler($encrypted),
            'database' => self::initializeDatabaseHandler($encrypted)
        };
    }

    /**
     * @throws SessionDatabaseStructureException
     * @throws SessionDatabaseTableException
     */
    private function initializeDatabaseHandler(bool $encrypted): void
    {
        $database = DatabaseSession::getInstance()->getDatabase();
        $table = $this->configurations['table']
            ?? self::DEFAULT_CONFIGURATIONS['table'];
        session_save_path(""); // Do not use for database ...
        $this->sessionHandler = $encrypted ?
            new EncryptedDatabaseSessionHandler($database, $table) :
            new DatabaseSessionHandler($database, $table);
        $this->sessionHandler->isAvailable();
    }

    /**
     * @throws SessionPathNotWritableException
     * @throws SessionPathNotExistException
     */
    private function initializeFileHandler(bool $encrypted): void
    {
        $savePath = $this->configurations['save_path']
            ?? self::DEFAULT_CONFIGURATIONS['save_path'];
        if (empty($savePath)) {
            $savePath = $this->getSystemSavePath();
        }
        $this->sessionHandler = $encrypted
            ? new EncryptedDefaultSessionHandler()
            : new DefaultSessionHandler();
        if ($this->sessionHandler->isAvailable($savePath)) {
            session_save_path($savePath);
        }
    }

    private function getSystemSavePath(): string
    {
        return !empty(session_save_path()) ? session_save_path() : sys_get_temp_dir();
    }
}
