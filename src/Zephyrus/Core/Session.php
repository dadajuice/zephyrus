<?php namespace Zephyrus\Core;

use Zephyrus\Core\Session\SessionConfiguration;
use Zephyrus\Core\Session\SessionFingerprintManager;
use Zephyrus\Core\Session\SessionIdentifierManager;
use Zephyrus\Exceptions\Session\SessionDatabaseStructureException;
use Zephyrus\Exceptions\Session\SessionDatabaseTableException;
use Zephyrus\Exceptions\Session\SessionDisabledException;
use Zephyrus\Exceptions\Session\SessionFingerprintException;
use Zephyrus\Exceptions\Session\SessionHttpOnlyCookieException;
use Zephyrus\Exceptions\Session\SessionLifetimeException;
use Zephyrus\Exceptions\Session\SessionPathNotExistException;
use Zephyrus\Exceptions\Session\SessionPathNotWritableException;
use Zephyrus\Exceptions\Session\SessionRefreshRateException;
use Zephyrus\Exceptions\Session\SessionRefreshRateProbabilityException;
use Zephyrus\Exceptions\Session\SessionStorageModeException;
use Zephyrus\Exceptions\Session\SessionSupportedRefreshModeException;
use Zephyrus\Exceptions\Session\SessionUseOnlyCookiesException;
use Zephyrus\Network\Request;
use Zephyrus\Utilities\FileSystem\File;

class Session
{
    private ?Request $request = null;
    private SessionConfiguration $configuration;
    private SessionFingerprintManager $fingerprintManager;
    private SessionIdentifierManager $identifierManager;

    /**
     * @param array $configurations
     * @throws SessionDisabledException
     * @throws SessionHttpOnlyCookieException
     * @throws SessionRefreshRateException
     * @throws SessionRefreshRateProbabilityException
     * @throws SessionSupportedRefreshModeException
     * @throws SessionUseOnlyCookiesException
     * @throws SessionDatabaseStructureException
     * @throws SessionDatabaseTableException
     * @throws SessionLifetimeException
     * @throws SessionPathNotExistException
     * @throws SessionPathNotWritableException
     * @throws SessionStorageModeException
     */
    public function __construct(array $configurations = SessionConfiguration::DEFAULT_CONFIGURATIONS)
    {
        // @codeCoverageIgnoreStart
        if (session_status() == PHP_SESSION_DISABLED) {
            throw new SessionDisabledException();
        }
        // @codeCoverageIgnoreEnd

        if (!ini_get('session.use_only_cookies')) {
            throw new SessionUseOnlyCookiesException();
        }

        if (!ini_get('session.cookie_httponly')) {
            throw new SessionHttpOnlyCookieException();
        }

        $this->configuration = new SessionConfiguration($configurations);
        $this->configuration->configure();
        $this->fingerprintManager = $this->configuration->buildFingerprint();
        $this->identifierManager = $this->configuration->buildIdentifier();
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * @throws SessionFingerprintException
     */
    public function start(): string
    {
        if (session_status() != PHP_SESSION_ACTIVE) {
            $this->identifierManager->configure();
            $this->fingerprintManager->start($this->request);
        }
        if ($this->fingerprintManager->isInitiated() && !$this->fingerprintManager->isValid($this->request)) {
            throw new SessionFingerprintException();
        }
        if ($this->identifierManager->isObsolete()) {
            $this->regenerate();
        }
        return $this->identifierManager->getId();
    }

    /**
     * Regenerates a session identifier for the current user session and deletes old session file. Useful to prevent
     * some form of session hijacking. Works automatically with the session refresher configurations (refresh_mode and
     * refresh_rate).
     *
     * @return string
     */
    public function regenerate(): string
    {
        session_regenerate_id(true);
        return session_id();
    }

    /**
     * Properly delete the entire session including the cookie expiration and all saved session data.
     */
    public static function destroy(): void
    {
        session_unset();
        setcookie(session_name(), '', 1);
        unset($_COOKIE[session_name()]);
        @session_destroy();
    }

    /**
     * Restart the entire session by regenerating the identifier and deleting all data.
     *
     * @return string
     */
    public function restart(): string
    {
        $this->destroy();
        $this->start();
        return session_id();
    }

    /**
     * Obtains the session value associated with the provided key. If the key doesn't exist in the current session, the
     * default value is thus returned (which is null if not defined by the user).
     *
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    public static function get(string $key, mixed $defaultValue = null): mixed
    {
        return $_SESSION[$key] ?? $defaultValue;
    }

    /**
     * Returns all data from the session.
     *
     * @return array
     */
    public static function getAll(): array
    {
        return $_SESSION;
    }

    /**
     * Adds or modifies a session value by providing the desired key and corresponding value. To make sure it does not
     * override an existing value, use the add method.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Adds the given key / value if the key does not already exist within the session. To update a previously defined
     * key, use the set method.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function add(string $key, mixed $value): bool
    {
        if (isset($_SESSION[$key])) {
            return false;
        }
        $_SESSION[$key] = $value;
        return true;
    }

    /**
     * Adds of modifies multiple session values at once.
     *
     * @param array $values
     */
    public static function setAll(array $values): void
    {
        foreach ($values as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Determines if the specified key exists in the current session. Optionally, if the value argument is used, it also
     * validates that the value is exactly the one provided.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function has(string $key, mixed $value = null): bool
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
    public static function remove(string $key): void
    {
        if (isset($_SESSION[$key])) {
            $_SESSION[$key] = '';
            unset($_SESSION[$key]);
        }
    }

    /**
     * Removes all the session data associated with the given keys.
     *
     * @param array $keys
     */
    public static function removeAll(array $keys): void
    {
        foreach ($keys as $key) {
            self::remove($key);
        }
    }

    /**
     * Empty all the session variables.
     *
     * @return void
     */
    public function clear(): void
    {
        session_unset();
    }

    /**
     * Retrieves the current session identifier.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->identifierManager->getId();
    }

    /**
     * Retrieves the current session name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return session_status() == PHP_SESSION_ACTIVE ? session_name() : null;
    }

    public function getIdentifierManager(): SessionIdentifierManager
    {
        return $this->identifierManager;
    }

    public function getFingerprintManager(): SessionFingerprintManager
    {
        return $this->fingerprintManager;
    }

    /**
     * Returns a File instance pointing to the current session file on the server when the session configuration is in
     * mode 'file'.
     *
     * @return File|null
     */
    public function getSessionFile(): ?File
    {
        $path = $this->configuration->getSavePath();
        $sessId = session_id();
        if (!$path || !$sessId) {
            return null;
        }
        return new File($path . '/sess_' . session_id());
    }
}
