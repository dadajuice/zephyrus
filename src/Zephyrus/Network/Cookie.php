<?php

namespace Zephyrus\Network;

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
    private $lifetime = self::DURATION_SESSION;

    /**
     * @var string The domain that the cookie is available to
     */
    private $domain = '';

    /**
     * @var string The path on the server which the cookie will be available
     *             on. If set to '/', the cookie will be available to the entire specified
     *             domain.
     */
    private $path = '/';

    /**
     * @var bool Determines if the cookie should be sent only over HTTPS from
     *           the client side.
     */
    private $secure = false;

    /**
     * @var bool Determines if the cookie is made accessible only through the
     *           HTTP protocol and thus making it inaccessible from scripting
     *           language.
     */
    private $httpOnly = true;

    /**
     * @var bool Determines if url encoding should be used for the cookie value
     */
    private $urlEncodedValue = false;

    /**
     * Cookie constructor which automatically create the cookie in the HTTP
     * response header when the <value> argument is given. Otherwise, the
     * constructor tries to load the value of the specified cookie name.
     *
     * @param string $name
     * @param string $value
     */
    public function __construct($name, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Save the cookie in the HTTP response header using the default PHP
     * functions setcookie or setrawcookie depending on the need to save the
     * cookie value without url encoding.
     *
     * @see http://php.net/manual/en/function.setcookie.php
     * @see http://php.net/manual/en/function.setrawcookie.php
     */
    public function send()
    {
        $args = [
            $this->name,
            $this->value,
            time() + $this->lifetime,
            $this->path,
            $this->domain,
            $this->secure,
            $this->httpOnly,
        ];
        $function = (!$this->urlEncodedValue) ? 'setrawcookie' : 'setcookie';
        call_user_func_array($function, $args);
        $_COOKIE[$this->name] = $this->value;
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
     * Apply a new value.
     *
     * @param string $value
     */
    public function setValue(string $value)
    {
        $this->value = $value;
    }

    /**
     * Apply a new expiration date.
     *
     * @param int $lifetime
     */
    public function setLifetime(int $lifetime)
    {
        $this->lifetime = $lifetime;
    }

    /**
     * Apply a new accessibility domain.
     *
     * @param string $domain
     */
    public function setDomain(string $domain)
    {
        $this->domain = $domain;
    }

    /**
     * Apply a new availability path. If '/' is specified, the cookie will be
     * available for the entire domain.
     *
     * @param string $path
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * Specify if the cookie should only be transmitted on HTTPS from the
     * client side.
     *
     * @param bool $secure
     */
    public function setSecure(bool $secure)
    {
        $this->secure = $secure;
    }

    /**
     * Specify if the cookie should be made accessible only though the HTTP
     * protocol, thus disabling access from scripting languages.
     *
     * @param bool $httpOnly
     */
    public function setHttpOnly(bool $httpOnly)
    {
        $this->httpOnly = $httpOnly;
    }

    /**
     * Specify if the cookie value is urlencoded (default FALSE).
     *
     * @param bool $urlEncodedValue
     */
    public function setIsValueUrlEncoded(bool $urlEncodedValue)
    {
        $this->urlEncodedValue = $urlEncodedValue;
    }

    /**
     * @return string Cookie name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string Cookie value
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
