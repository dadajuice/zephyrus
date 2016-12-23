<?php namespace Zephyrus\Security;

use Zephyrus\Application\SessionHandler;

class Session
{
    /**
     * @var Session unique class instance (singleton)
     */
    private static $instance = null;

    /**
     * @var string cookie name which holds the session identifier
     */
    private $name;

    /**
     * @var int controls the expiration time of the cookie (default : 0)
     */
    private $lifetime;

    /**
     * @var string domain validity of the cookie
     */
    private $domain;

    /**
     * @var string path validity (from domain) of the cookie (default : /)
     */
    private $path;

    /**
     * @var bool defines if the cookie should be transmitted only over HTTPS
     */
    private $secure;

    /**
     * @var bool defines if the cookie should be accessible only through HTTP (default : true)
     */
    private $httpOnly;

    /**
     * @var bool defines if the user agent should be considered when creating the fingerprint
     */
    private $userAgentFingerprinted;

    /**
     * @var bool defines if the ip address should be considered when creating the fingerprint
     */
    private $ipAddressFingerprinted;

    /**
     * @var int number of requests before automatically refreshing the identifier
     */
    private $refreshAfterNthRequests;

    /**
     * @var int number of seconds before automatically refreshing the identifier
     */
    private $refreshAfterInterval;

    /**
     * @var float probability (in float percent 0-1) of a random identifier refresh
     */
    private $refreshProbability;

    /**
     * @var string[] array of decoy cookie names to be sent on session start
     */
    private $decoys = [];

    /**
     * @var bool defines if the stored session date should be encrypted
     */
    private $encryptionEnabled = false;

    /**
     * @var string In case encryption is enabled, this defined the MCRYPT
     * compatible algorithm to use.
     */
    private $encryptionAlgorithm = null;

    /**
     * @var SessionHandler
     */
    private $sessionHandler = null;

    /**
     * Obtain the single allowed instance for Session through singleton pattern
     * method.
     *
     * @param mixed[] $config
     * @return Session
     */
    public static function getInstance($config = null)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($config);
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
        $this->configure();
        session_start();
        $this->setupRefreshOnNthRequestsHandler();
        $this->setupRefreshOnIntervalHandler();
        $this->setupFingerprintHandler();

        if (!isset($_SESSION['__HANDLER_INITIATED'])) {
            session_regenerate_id(true);
            if (!is_null($this->decoys)) {
                $this->throwDecoys();
            }
            $_SESSION['__HANDLER_INITIATED'] = true;
        } else {
            if (!$this->hasValidFingerprint()) {
                throw new \Exception("Session fingerprint doesn't match");
            }
            if ($this->refreshAfterNthRequests == 1 ||
                $this->isRefreshNeededByProbability() || $this->isObsolete()) {
                $this->refresh();
            }
        }
    }

    /**
     * Securely destroy the active session. Automatically expires decoy cookies
     * if set.
     */
    public function destroy()
    {
        $_SESSION = [];
        setcookie($this->name, '', 1);
        unset($_COOKIE[session_name()]);
        if (!empty($this->decoys)) {
            $this->destroyDecoys();
        }
        session_destroy();
    }

    /**
     * Refresh session identifier and expiration variables
     */
    public function refresh()
    {
        session_regenerate_id(true);
        $this->initiateExpiration();
    }

    /**
     * Restart the entire session by regenerating the identifier, deleting all
     * data and initiating handlers.
     */
    public function restart()
    {
        $this->destroy();
        $this->start();
    }

    /**
     * Puts the current active session in read only states preventing any
     * modification from this point till the next request.
     */
    public function readOnly()
    {
        session_write_close();
    }

    /**
     * Add a decoy cookie with the specified name that will be thrown along the
     * session cookie. This doesn't contribute in pure security measures, but
     * contribute in hiding server footprints and add a little more confusion
     * to the overall communication.
     *
     * @param string $name
     */
    public function addDecoy($name)
    {
        $this->deniedChangesWhenSessionStarted();
        if (is_null($this->decoys)) {
            $this->decoys = [];
        }
        $this->decoys[] = $name;
    }

    /**
     * Add a certain amount of random decoy cookies that will be sent along
     * the session cookie. This doesn't contribute in pure security measures,
     * but contribute in hiding server footprints and add a little more
     * confusion to the overall communication.
     *
     * @param int $count
     */
    public function addRandomDecoys($count)
    {
        $this->deniedChangesWhenSessionStarted();
        for ($i = 0; $i < $count; ++$i) {
            $this->addDecoy(Cryptography::randomString(20));
        }
    }

    /**
     * @return string
     */
    public function getSavePath()
    {
        $savePath = session_save_path();
        return (empty($savePath))
            ? sys_get_temp_dir()
            : $savePath;
    }

    /**
     * @return bool
     */
    public function isEncryptionEnabled()
    {
        return $this->encryptionEnabled;
    }

    /**
     * @param bool $encryptionEnabled
     */
    public function setEncryptionEnabled($encryptionEnabled)
    {
        $this->deniedChangesWhenSessionStarted();
        $this->encryptionEnabled = (bool)$encryptionEnabled;
    }

    /**
     * @return string
     */
    public function getEncryptionAlgorithm() {
        return $this->encryptionAlgorithm;
    }

    /**
     * @param $encryptionAlgorithm
     */
    public function setEncryptionAlgorithm($encryptionAlgorithm)
    {
        $this->deniedChangesWhenSessionStarted();
        $this->encryptionAlgorithm = $encryptionAlgorithm;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @throws \RuntimeException
     */
    public function setName($name)
    {
        $this->deniedChangesWhenSessionStarted();
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getLifetime()
    {
        return (int)$this->lifetime;
    }

    /**
     * @param int $lifetime
     * @throws \RuntimeException
     */
    public function setLifetime($lifetime)
    {
        $this->deniedChangesWhenSessionStarted();
        $this->lifetime = (int)$lifetime;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     * @throws \RuntimeException
     */
    public function setDomain($domain)
    {
        $this->deniedChangesWhenSessionStarted();
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @throws \RuntimeException
     */
    public function setPath($path)
    {
        $this->deniedChangesWhenSessionStarted();
        $this->path = $path;
    }

    /**
     * @return boolean
     */
    public function isSecure()
    {
        return (bool)$this->secure;
    }

    /**
     * @param boolean $secure
     * @throws \RuntimeException
     */
    public function setSecure($secure)
    {
        $this->deniedChangesWhenSessionStarted();
        $this->secure = (bool)$secure;
    }

    /**
     * @return boolean
     */
    public function isHttpOnly()
    {
        return (bool)$this->httpOnly;
    }

    /**
     * @param boolean $httpOnly
     * @throws \RuntimeException
     */
    public function setHttpOnly($httpOnly)
    {
        $this->deniedChangesWhenSessionStarted();
        $this->httpOnly = (bool)$httpOnly;
    }

    /**
     * @return boolean
     */
    public function isUserAgentFingerprinted()
    {
        return (bool)$this->userAgentFingerprinted;
    }

    /**
     * @param boolean $userAgentFingerprinted
     * @throws \RuntimeException
     */
    public function setUserAgentFingerprinted($userAgentFingerprinted)
    {
        $this->deniedChangesWhenSessionStarted();
        $this->userAgentFingerprinted = (bool)$userAgentFingerprinted;
    }

    /**
     * @return boolean
     */
    public function isIpAddressFingerprinted()
    {
        return (bool)$this->ipAddressFingerprinted;
    }

    /**
     * @param boolean $ipAddressFingerprinted
     * @throws \RuntimeException
     */
    public function setIpAddressFingerprinted($ipAddressFingerprinted)
    {
        $this->deniedChangesWhenSessionStarted();
        $this->ipAddressFingerprinted = (bool)$ipAddressFingerprinted;
    }

    /**
     * @return int
     */
    public function getRefreshAfterNthRequests()
    {
        return $this->refreshAfterNthRequests;
    }

    /**
     * @param int $refreshAfterNthRequests
     * @throws \RuntimeException
     */
    public function setRefreshAfterNthRequests($refreshAfterNthRequests)
    {
        $this->deniedChangesWhenSessionStarted();
        $this->refreshAfterNthRequests = (int)$refreshAfterNthRequests;
    }

    /**
     * @return int
     */
    public function getRefreshAfterInterval()
    {
        return $this->refreshAfterInterval;
    }

    /**
     * @param int $refreshAfterInterval
     * @throws \RuntimeException
     */
    public function setRefreshAfterInterval($refreshAfterInterval)
    {
        $this->deniedChangesWhenSessionStarted();
        $this->refreshAfterInterval = (int)$refreshAfterInterval;
    }

    /**
     * @return float
     */
    public function getRefreshProbability()
    {
        return $this->refreshProbability;
    }

    /**
     * @param float $refreshProbability
     * @throws \RuntimeException
     */
    public function setRefreshProbability($refreshProbability)
    {
        $this->deniedChangesWhenSessionStarted();
        if ($refreshProbability < 0 || $refreshProbability > 1) {
            throw new \RangeException("Probability must be between 0 and 1");
        }
        $this->refreshProbability = (float)$refreshProbability;
    }

    /**
     * Session constructor with default settings. Verify if default PHP
     * configuration uses cookie as transport mechanism. Otherwise throws
     * exception. Also make sure the cookie is always accessible through HTTP
     * only and automatically make it secure when HTTPS connection is used.
     *
     * @param mixed[] $config
     * @throws \Exception
     */
    private function __construct($config = null)
    {
        $this->name = isset($config['name'])
            ? $config['name']
            : 'PHPSESSID';
        $this->lifetime = ini_get('session.cookie_lifetime');
        $this->path = ini_get('session.cookie_path');
        $this->domain = ini_get('session.cookie_domain');
        $this->secure = (isset($_SERVER['HTTPS'])
            ? true
            : ini_get('session.cookie_secure'));
        $this->httpOnly = true;
        $this->userAgentFingerprinted = isset($config['fingerprint_agent'])
            ? $config['fingerprint_agent']
            : true;
        $this->ipAddressFingerprinted = isset($config['fingerprint_ip'])
            ? $config['fingerprint_ip']
            : false;
        $this->refreshAfterNthRequests = isset($config['refresh_after_requests'])
            ? $config['refresh_after_requests']
            : null;
        $this->refreshAfterInterval = isset($config['refresh_after_interval'])
            ? $config['refresh_after_interval']
            : null;
        $this->refreshProbability = isset($config['refresh_probability'])
            ? $config['refresh_probability']
            : null;
        $this->encryptionEnabled = isset($config['encryption_enabled'])
            ? $config['encryption_enabled']
            : false;
        $this->encryptionAlgorithm = isset($config['encryption_algorithm'])
            ? $config['encryption_algorithm']
            : false;
        if (isset($config['decoys'])) {
            if (is_numeric($config['decoys'])) {
                $this->addRandomDecoys($config['decoys']);
            } else {
                if (is_array($config['decoys'])) {
                    foreach ($config['decoys'] as $decoyName) {
                        $this->addDecoy($decoyName);
                    }
                } else {
                    $this->decoys = null;
                }
            }
        } else {
            $this->decoys = null;
        }

        if (!ini_get('session.use_cookies') || !ini_get('session.use_only_cookies')) {
            throw new \Exception("Session configurations are not secure.
            Fixation may be possible. Please review your php.ini or local
            settings (eg. .htaccess) for directive session.use_cookies and
            session.use_only_cookies.");
        }
    }

    private function setupRefreshOnNthRequestsHandler()
    {
        if (empty($this->refreshAfterNthRequests)) {
            if (isset($_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'])) {
                unset($_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH']);
            }
        } else {
            if (!isset($_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'])) {
                $_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'] = $this->refreshAfterNthRequests;
            }
        }
    }

    private function setupRefreshOnIntervalHandler()
    {
        if (empty($this->refreshAfterInterval)) {
            if (isset($_SESSION['__HANDLER_SECONDS_BEFORE_REFRESH'])) {
                unset($_SESSION['__HANDLER_SECONDS_BEFORE_REFRESH']);
            }
            if (isset($_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP'])) {
                unset($_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP']);
            }
        } else {
            if (!isset($_SESSION['__HANDLER_SECONDS_BEFORE_REFRESH'])) {
                $_SESSION['__HANDLER_SECONDS_BEFORE_REFRESH'] = $this->refreshAfterInterval;
                $_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP'] = time();
            }
        }
    }

    /**
     * Initiates session fingerprint based on user agent configurations and / or
     * ip address. Fingerprint which includes ip address might not work with
     * proxies based networks.
     */
    private function setupFingerprintHandler()
    {
        if (!$this->userAgentFingerprinted && !$this->ipAddressFingerprinted) {
            if (isset($_SESSION['__HANDLER_FINGERPRINT'])) {
                unset($_SESSION['__HANDLER_FINGERPRINT']);
            }
        } else {
            if (!isset($_SESSION['__HANDLER_FINGERPRINT'])) {
                $_SESSION['__HANDLER_FINGERPRINT'] = $this->createFingerprint();
            }
        }
    }

    /**
     * Initiates expiration policies for the current session based on automated
     * refreshes after nth requests and/or after a certain time interval.
     */
    private function initiateExpiration()
    {
        if (!empty($this->refreshAfterNthRequests)) {
            $_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'] = $this->refreshAfterNthRequests;
        }
        if (!empty($this->refreshAfterInterval)) {
            $_SESSION['__HANDLER_SECONDS_BEFORE_REFRESH'] = $this->refreshAfterInterval;
            $_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP'] = time();
        }
    }

    /**
     * Determines if the session needs to be refreshed either because the
     * maximum number of allowed requests has been reached or the timeout has
     * finished.
     *
     * @return bool
     */
    private function isObsolete()
    {
        if (isset($_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'])) {
            if ($_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'] < 1) {
                return true;
            } else {
                $_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH']--;
            }
        }
        if (isset($_SESSION['__HANDLER_SECONDS_BEFORE_REFRESH'])) {
            $timeDifference = time() - $_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP'];
            if ($timeDifference >= $_SESSION['__HANDLER_SECONDS_BEFORE_REFRESH']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determines if the stored fingerprint match the data sent with the
     * current HTTP request.
     *
     * @return bool
     */
    private function hasValidFingerprint()
    {
        if (isset($_SESSION['__HANDLER_FINGERPRINT'])) {
            return $_SESSION['__HANDLER_FINGERPRINT'] === $this->createFingerprint();
        }
        return true;
    }

    /**
     * Determines if the probability test of session refresh succeeded
     * according to the desired percent.
     *
     * @return bool
     */
    private function isRefreshNeededByProbability()
    {
        if ($this->refreshProbability == null) {
            return false;
        }
        if ($this->refreshProbability == 1.0) {
            return true;
        }
        $rand = (float)mt_rand() / (float)mt_getrandmax();
        if ($rand <= $this->refreshProbability) {
            return true;
        }
        return false;
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
            $fingerprint .= (getenv('HTTP_X_FORWARDED_FOR'))
                ? getenv('HTTP_X_FORWARDED_FOR')
                : getenv('REMOTE_ADDR');
        }
        if ($this->userAgentFingerprinted) {
            $fingerprint .= $_SERVER['HTTP_USER_AGENT'];
        }
        return hash('sha256', $fingerprint);
    }

    /**
     * Throw decoy cookies at session start which are configured exactly as the
     * session cookie. This doesn't contribute in pure security measures, but
     * contribute in hiding server footprints and add a little more confusion
     * to the overall communication.
     */
    private function throwDecoys()
    {
        $params = session_get_cookie_params();
        $len = strlen(session_id());

        foreach ($this->decoys as $decoy) {
            $value = Cryptography::randomString($len);
            setcookie(
                $decoy,
                $value,
                $params['lifetime'],
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
    }

    /**
     * Carefully expires all decoy cookies. Should be called only if decoys are
     * set and on session destroy.
     *
     * @see destroy()
     */
    private function destroyDecoys()
    {
        foreach ($this->decoys as $decoy) {
            setcookie($decoy, '', 1);
            setcookie($decoy, false);
            unset($_COOKIE[$decoy]);
        }
    }

    /**
     * Configure session cookie parameters and name. Automatically called on
     * session start.
     */
    private function configure()
    {
        session_set_cookie_params($this->lifetime,
                                  $this->path,
                                  $this->domain,
                                  $this->secure,
                                  $this->httpOnly
        );
        session_name($this->name);
        if ($this->encryptionEnabled) {
            $this->sessionHandler = new EncryptedSessionHandler();
            if (!empty($this->encryptionAlgorithm)) {
                $this->sessionHandler->setEncryptionAlgorithm($this->encryptionAlgorithm);
            }
        }
    }

    /**
     * Called on each setters to prevent object changes when session is already
     * started.
     *
     * @throws \RuntimeException
     */
    private function deniedChangesWhenSessionStarted()
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            throw new \RuntimeException("Session settings cannot be changed when started");
        }
    }
}