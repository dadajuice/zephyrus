<?php namespace Zephyrus\Application\Session;

use Exception;
use SessionHandler;
use Zephyrus\Security\Cryptography;

class EncryptedSessionHandler extends SessionHandler
{
    /**
     * Symmetric key for encryption. This key will be sent to the client via cookie meaning only the client will be
     * able to unlock his session data. The session file on the server will be encrypted and the only key able to open
     * it is with the client cookie jar.
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
     * Called on session_start, this method creates the cookie that will allow the session unlocking. Meaning, only the
     * authenticated user with the proper cookie will be able to read the session ensuring that if the server is somehow
     * compromised, the active session data won't be readable.
     *
     * @param string $path
     * @param string $name
     * @throws Exception
     * @return bool
     */
    public function open(string $path, string $name): bool
    {
        parent::open($path, $name);
        $this->cookieKeyName = "key_$name";
        if (!isset($_COOKIE[$this->cookieKeyName])) {
            $this->createEncryptionCookie();
            return true;
        }
        $this->encryptionKey = $_COOKIE[$this->cookieKeyName];
        return true;
    }

    /**
     * Overrides the session handler read method to make sure to properly decrypt the data contained within using the
     * encryption key store in the user's cookies.
     *
     * @param string $id
     * @return string|null
     */
    public function read(string $id): ?string
    {
        $data = parent::read($id);
        return (!$data) ? "" : Cryptography::decrypt($data, $this->encryptionKey);
    }

    /**
     * Overrides the session handler write method to make sure to properly encrypt the data before saving it using the
     * encryption key store in the user's cookies.
     *
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write(string $id, string $data): bool
    {
        $data = Cryptography::encrypt($data, $this->encryptionKey);
        return parent::write($id, $data);
    }

    /**
     * Destroy session file on disk and delete encryption cookie if no session is active after deletion.
     *
     * @param string $id
     * @return bool
     */
    public function destroy(string $id): bool
    {
        if (isset($_COOKIE[$this->cookieKeyName])) {
            setcookie($this->cookieKeyName, '', 1);
            unset($_COOKIE[$this->cookieKeyName]);
        }
        return true;
    }

    /**
     * Prepares and sends the cookie with the encryption key to the client using the same cookie configurations as the
     * session to ensure coherence.
     */
    private function createEncryptionCookie()
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
        setrawcookie($this->cookieKeyName, $this->encryptionKey, $options);
    }
}
