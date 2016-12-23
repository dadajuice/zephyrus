<?php namespace Zephyrus\Security;

use Zephyrus\Exceptions\InvalidCsrfException;
use Zephyrus\Network\Request;

class CsrfFilter
{
    const HEADER_NAME = 'HTTP_X_CSRF_TOKEN';
    const TOKEN_NAME = '__CSRF_TOKEN';
    const TOKEN_LENGTH = 48;

    /**
     * Singleton pattern instance.
     *
     * @var CsrfFilter
     */
    private static $instance = null;

    /**
     * Determines if the CSRF filter is active for the current request.
     *
     * @var bool
     */
    private $active = true;

    /**
     * Determines if the HTTP GET requests are secured by the CSRF filter. It
     * implies that for EVERY request of this type, the CSRF token should be
     * provided.
     *
     * @var bool
     */
    private $getSecured = false;

    /**
     * Determines if the HTTP POST requests are secured by the CSRF filter. It
     * implies that for EVERY request of this type, the CSRF token should be
     * provided.
     *
     * @var bool
     */
    private $postSecured = true;

    /**
     * Determines if the HTTP PUT requests are secured by the CSRF filter. It
     * implies that for EVERY request of this type, the CSRF token should be
     * provided.
     *
     * @var bool
     */
    private $putSecured = true;

    /**
     * Determines if the HTTP DELETE requests are secured by the CSRF filter. It
     * implies that for EVERY request of this type, the CSRF token should be
     * provided.
     *
     * @var bool
     */
    private $deleteSecured = true;

    /**
     * @return CsrfFilter
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Generates and stores in the current session a cryptographically random
     * token that shall be validated with the filter method.
     *
     * @param string $name
     * @return string
     * @throws \Exception
     */
    public static function generateToken($name = self::TOKEN_NAME)
    {
        $token = Cryptography::randomString(self::TOKEN_LENGTH);
        $_SESSION[$name] = $token;
        return $token;
    }

    /**
     * Displays the corresponding hidden field to match CSRF validation.
     */
    public static function displayHiddenField()
    {
        ?><input type="hidden" name="<?= self::TOKEN_NAME; ?>" value="<?= $_SESSION[self::TOKEN_NAME]; ?>" /><?php
    }

    /**
     * Proceed to filter the request.
     *
     * @throws InvalidCsrfException
     */
    public function filter()
    {
        if ($this->active && $this->isHttpMethodFiltered(Request::getMethod())) {
            $this->validateToken();
            if (!$this->isXhrBasedRequest()) {
                self::generateToken();
            }
        }
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return boolean
     */
    public function isGetSecured()
    {
        return $this->getSecured;
    }

    /**
     * @param boolean $getSecured
     */
    public function setGetSecured($getSecured)
    {
        $this->getSecured = $getSecured;
    }

    /**
     * @return boolean
     */
    public function isPostSecured()
    {
        return $this->postSecured;
    }

    /**
     * @param boolean $postSecured
     */
    public function setPostSecured($postSecured)
    {
        $this->postSecured = $postSecured;
    }

    /**
     * @return boolean
     */
    public function isPutSecured()
    {
        return $this->putSecured;
    }

    /**
     * @param boolean $putSecured
     */
    public function setPutSecured($putSecured)
    {
        $this->putSecured = $putSecured;
    }

    /**
     * @return boolean
     */
    public function isDeleteSecured()
    {
        return $this->deleteSecured;
    }

    /**
     * @param boolean $deleteSecured
     */
    public function setDeleteSecured($deleteSecured)
    {
        $this->deleteSecured = $deleteSecured;
    }

    /**
     * Checks if the specified method should be filtered.
     *
     * @param string $method
     * @return bool
     */
    private function isHttpMethodFiltered($method)
    {
        $method = strtoupper($method);
        if ($this->getSecured && $method == "GET") {
            return true;
        } elseif ($this->postSecured && $method == "POST") {
            return true;
        } elseif ($this->putSecured && $method == "PUT") {
            return true;
        } elseif ($this->deleteSecured && $method == "DELETE") {
            return true;
        }
        return false;
    }

    /**
     * Validates if there is a provided CSRF token and if it matches the stored
     * value.
     *
     * @throws InvalidCsrfException
     */
    private function validateToken()
    {
        $providedToken = $this->getProvidedCsrfToken();
        $storedToken = $this->getStoredCsrfToken();

        if (is_null($providedToken)) {
            // exception : no csrf token provided
            throw new InvalidCsrfException();
        }

        if (is_null($storedToken)) {
            // exception : csrf token provided, but not stored
            throw new InvalidCsrfException();
        }

        if ($storedToken != $providedToken) {
            throw new InvalidCsrfException();
        }
    }

    /**
     * Obtains the CSRF token provided by the client either by request data
     * or by an HTTP header (e.g. Ajax based requests). Returns null if
     * undefined.
     *
     * @return null|string
     */
    private function getProvidedCsrfToken()
    {
        $token = Request::getParameter(self::TOKEN_NAME);
        if (is_null($token)) {
            $token = isset($_SERVER[self::HEADER_NAME]) ? $_SERVER[self::HEADER_NAME] : null;
        }
        return $token;
    }

    /**
     * Obtains the CSRF token stored by the server for the corresponding
     * client. Returns null if undefined.
     *
     * @return null|string
     */
    private function getStoredCsrfToken()
    {
        return isset($_SESSION[self::TOKEN_NAME]) ? $_SESSION[self::TOKEN_NAME] : null;
    }

    /**
     * Determines if the client request is Ajax based.
     *
     * @return bool
     */
    private function isXhrBasedRequest()
    {
        return isset($_SERVER[self::HEADER_NAME]);
    }

    /**
     * Private CsrfFilter constructor for singleton pattern.
     */
    private function __construct() {}
}