<?php namespace Zephyrus\Core\Session;

use Zephyrus\Security\Cryptography;

class SessionEncryption
{
    /**
     * Symmetric key for encryption. This key will be sent to the client via cookie meaning only the client will be
     * able to unlock his session data. The session data on the server will be encrypted and the only key able to open
     * it is in the client cookie jar.
     *
     * @var string
     */
    private string $encryptionKey;

    /**
     * The cookie name that will store the encryption data (hmac and symmetric key).
     *
     * @var string
     */
    private string $cookieKeyName;

    /**
     * Must be called upon the session_start (open method of the SessionHandlerInterface).
     *
     * @param string $sessionName
     */
    public function __construct(string $sessionName)
    {
        $this->cookieKeyName = "key_$sessionName";
        if (!isset($_COOKIE[$this->cookieKeyName])) {
            $this->createEncryptionCookie();
        }
        $this->encryptionKey = $_COOKIE[$this->cookieKeyName];
    }

    public function decrypt(string $data): ?string
    {
        return (!$data) ? "" : Cryptography::decrypt($data, $this->encryptionKey);
    }

    public function encrypt(string $data): string
    {
        return Cryptography::encrypt($data, $this->encryptionKey);
    }

    public function destroy(): void
    {
        if (isset($_COOKIE[$this->cookieKeyName])) {
            setcookie($this->cookieKeyName, '', 1);
            unset($_COOKIE[$this->cookieKeyName]);
        }
    }

    /**
     * Prepares and sends the cookie with the encryption key to the client using the same cookie configurations as the
     * session to ensure coherence.
     */
    private function createEncryptionCookie(): void
    {
        $this->encryptionKey = Cryptography::randomString(16);
        $cookieSettings = session_get_cookie_params();
        $options = [
            'expires' => ($cookieSettings['lifetime'] > 0) ? time() + $cookieSettings['lifetime'] : 0,
            'path' => $cookieSettings['path'],
            'domain' => $cookieSettings['domain'],
            'secure' => $cookieSettings['secure'],
            'httponly' => $cookieSettings['httponly'],
            'samesite' => $cookieSettings['samesite']
        ];
        $_COOKIE[$this->cookieKeyName] = $this->encryptionKey;
        setrawcookie($this->cookieKeyName, $this->encryptionKey, $options);
    }
}
