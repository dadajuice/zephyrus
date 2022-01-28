<?php namespace Zephyrus\Network;

class Cookie
{
    public const DURATION_SESSION = null;
    public const DURATION_HOUR = 3600;
    public const DURATION_DAY = 86400;
    public const DURATION_WEEK = 604800;
    public const DURATION_MONTH = 2419200;
    public const DURATION_YEAR = 29030400;
    public const DURATION_FOREVER = 145152000; // 5 years

    /**
     * Cookie name used to identify.
     *
     * @var string
     */
    private string $name;

    /**
     * Cookie value which will end stored on clients' side.
     *
     * @var string|null
     */
    private ?string $value;

    /**
     * Cookie expire time (timestamp).
     *
     * @var int
     */
    private int $lifetime = self::DURATION_SESSION;

    /**
     * The domain that the cookie is available to.
     *
     * @var string
     */
    private string $domain = '';

    /**
     * The path on the server which the cookie will be available on. If set to '/' (default value), the cookie will be
     * available to the entire specified domain.
     *
     * @var string
     */
    private string $path = '/';

    /**
     * Determines if the cookie should be sent only over HTTPS from the client side.
     *
     * @var bool
     */
    private bool $secure = true;

    /**
     * Determines if the cookie is made accessible only through the HTTP protocol and thus making it inaccessible from
     * frontend scripting language.
     *
     * @var bool
     */
    private bool $httpOnly = true;

    /**
     * Determines if url encoding should be used for the cookie value.
     *
     * @var bool
     */
    private bool $urlEncodedValue = false;

    /**
     * @var string
     */
    private string $sameSite = 'Strict';

    /**
     * Shorthand method to immediately read a cookie value or receive the default value in case the cookie doesn't
     * exist.
     *
     * @param string $name
     * @param string|null $defaultValue
     * @return string|null
     */
    public static function read(string $name, ?string $defaultValue = null): ?string
    {
        return $_COOKIE[$name] ?? $defaultValue;
    }

    /**
     * Cookie constructor which automatically create the cookie in the HTTP response header upon usage of the send
     * method once it's fully configured.
     *
     * @param string $name
     * @param string|null $value
     */
    public function __construct(string $name, ?string $value = null)
    {
        $this->name = $name;
        $this->value = (!is_null($value)) ? $value : self::read($name);
    }

    /**
     * Save the cookie in the HTTP response header using the default PHP functions setcookie or setrawcookie depending
     * on the need to save the cookie value without url encoding.
     *
     * @see http://php.net/manual/en/function.setcookie.php
     * @see http://php.net/manual/en/function.setrawcookie.php
     */
    public function send()
    {
        $options = [
            'expires' => time() + $this->lifetime,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure && $_SERVER['HTTPS'],
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite
        ];
        $args = [
            $this->name,
            $this->value,
            $options
        ];
        $function = (!$this->urlEncodedValue) ? 'setrawcookie' : 'setcookie';
        call_user_func_array($function, $args);
        $_COOKIE[$this->name] = $this->value;
    }

    /**
     * Correctly destroy cookie currently loaded. Does not verify for time() to expire the cookie to avoid any time
     * corruption on either sides.
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
     * Apply a new expiration date using a timestamp.
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
     * Specify if the cookie should only be transmitted on HTTPS from the client side if possible. Defaults to true. If
     * the server doesn't support HTTPS, it will revert to a non-secure solution automatically. Should always be true.
     *
     * @param bool $secure
     */
    public function setSecure(bool $secure)
    {
        $this->secure = $secure;
    }

    /**
     * Specify if the cookie should be made accessible only though the HTTP protocol, thus disabling access from
     * scripting languages. Defaults to true and should always be true for security reasons.
     *
     * @param bool $httpOnly
     */
    public function setHttpOnly(bool $httpOnly)
    {
        $this->httpOnly = $httpOnly;
    }

    /**
     * Restricts the cookie to the ownership context (RFC6265bis).
     *
     * @see https://web.dev/samesite-cookies-explained/
     * @param string $sameSite
     */
    public function setSameSite(string $sameSite)
    {
        $possibleValues = ['None', 'Lax', 'Strict'];
        if (!in_array($sameSite, $possibleValues)) {
            throw new \InvalidArgumentException("The Cookie samesite property must be one of the following : Lax, None or Strict.");
        }
        $this->sameSite = $sameSite;
    }

    /**
     * Specify if the cookie value is urlencoded. Defaults to false. When its false, the rawcookie function will be
     * used to send information.
     *
     * @param bool $urlEncodedValue
     */
    public function setIsValueUrlEncoded(bool $urlEncodedValue)
    {
        $this->urlEncodedValue = $urlEncodedValue;
    }

    /**
     * Retrieves the given name of the cookie.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retrieves the value given to the cookie or the loaded value if applicable. If no cookie was loaded, the value
     * will be NULL.
     *
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }
}
