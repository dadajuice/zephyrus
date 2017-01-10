<?php namespace Zephyrus\Network;

class Cookie
{
    const DURATION_SESSION = null;
    const DURATION_HOUR = 3600;
    const DURATION_DAY = 86400;
    const DURATION_WEEK = 604800;
    const DURATION_MONTH = 2419200;
    const DURATION_YEAR = 29030400;
    const DURATION_FOREVER = 145152000; // 5 years

    /**
     * @var string Cookie name used to identify
     */
    private $name;

    /**
     * @var string Cookie value which will end stored on clients' side
     */
    private $value;

    /**
     * @var int Cookie expire time (timestamp)
     */
    private $lifetime;

    /**
     * @var string The domain that the cookie is available to
     */
    private $domain;

    /**
     * @var string The path on the server which the cookie will be available
     * on. If set to '/', the cookie will be available to the entire specified
     * domain.
     */
    private $path;

    /**
     * @var bool Determines if the cookie should be sent only over HTTPS from
     * the client side.
     */
    private $secure;

    /**
     * @var bool Determines if the cookie is made accessible only through the
     * HTTP protocol and thus making it inaccessible from scripting
     * language.
     */
    private $httpOnly;

    /**
     * @var bool Determines if url encoding should be used for the cookie value
     */
    private $urlEncodedValue;

    /**
     * Cookie constructor which automatically create the cookie in the HTTP
     * response header when the <value> argument is given. Otherwise, the
     * constructor tries to load the value of the specified cookie name.
     *
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param bool $urlEncodedValue
     */
    public function __construct($name, $value = null,
                                $expire = self::DURATION_SESSION,
                                $path = '/', $domain = '', $secure = false,
                                $httpOnly = true, $urlEncodedValue = false)
    {
        $this->name = $name;
        $this->value = $value;
        $this->lifetime = $expire;
        $this->path = $path;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->urlEncodedValue = $urlEncodedValue;
        if (is_null($value)) {
            $this->load();
        } else {
            $this->setCookie();
        }
    }

    /**
     * @return bool
     */
    public function exists()
    {
        return isset($_COOKIE[$this->name]);
    }

    /**
     * Correctly destroy cookie currently loaded. Does not verify for time() to
     * expire the cookie to avoid any time corruption on either sides.
     */
    public function destroy()
    {
        if (isset($_COOKIE[$this->name])) {
            setcookie($this->name, '', 1);
            setcookie($this->name, false);
            unset($_COOKIE[$this->name]);
        }
    }

    /**
     * Apply a new value. Updates cookie immediately.
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
        $this->setCookie();
    }

    /**
     * Apply a new expiration date. Updates cookie immediately.
     *
     * @param int $lifetime
     */
    public function setLifetime($lifetime)
    {
        $this->lifetime = (int)$lifetime;
        $this->setCookie();
    }

    /**
     * Apply a new accessibility domain. Update cookie immediately.
     *
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        $this->setCookie();
    }

    /**
     * Apply a new availability path. If '/' is specified, the cookie will be
     * available for the entire domain. Updates cookie immediately.
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
        $this->setCookie();
    }

    /**
     * Specify if the cookie should only be transmitted on HTTPS from the
     * client side. Updates cookie immediately.
     *
     * @param bool $secure
     */
    public function setSecure($secure)
    {
        $this->secure = (bool)$secure;
        $this->setCookie();
    }

    /**
     * Specify if the cookie should be made accessible only though the HTTP
     * protocol, thus disabling access from scripting languages.
     *
     * @param bool $httpOnly
     */
    public function setHttpOnly($httpOnly)
    {
        $this->httpOnly = (bool)$httpOnly;
        $this->setCookie();
    }

    /**
     * Specify if the cookie value is urlencoded (default TRUE). Updates
     * cookie immediately.
     *
     * @param bool $urlEncodedValue
     */
    public function setIsValueUrlEncoded($urlEncodedValue)
    {
        $this->urlEncodedValue = (bool)$urlEncodedValue;
        $this->setCookie();
    }

    /**
     * @return string Cookie name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string Cookie value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Save the cookie in the HTTP response header using the default PHP
     * functions setcookie or setrawcookie depending on the need to save the
     * cookie value without url encoding.
     *
     * @see http://php.net/manual/en/function.setcookie.php
     * @see http://php.net/manual/en/function.setrawcookie.php
     */
    private function setCookie()
    {
        if (!$this->urlEncodedValue) {
            setrawcookie(
                $this->name,
                $this->value,
                time() + $this->lifetime,
                $this->path,
                $this->domain,
                $this->secure,
                $this->httpOnly
            );
        } else {
            setcookie(
                $this->name,
                $this->value,
                time() + $this->lifetime,
                $this->path,
                $this->domain,
                $this->secure,
                $this->httpOnly
            );
        }
    }

    /**
     * Loads the saved cookie value if it exists.
     */
    private function load()
    {
        if (isset($_COOKIE[$this->name])) {
            $this->value = $_COOKIE[$this->name];
        }
    }
}