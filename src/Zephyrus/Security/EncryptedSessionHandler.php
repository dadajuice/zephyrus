<?php namespace Zephyrus\Security;

class EncryptedSessionHandler extends \SessionHandler
{
    /**
     * @var string Encryption symmetric key created using the specified algorithm
     */
    private $encryptionKey;

    /**
     * @var string Cookie name that will store the encryption data (hmac and symmetric key).
     */
    private $cookieKeyName;

    /**
     * Called on session_start, this method creates the cookie that will allow the session unlocking. Meaning, only the
     * authenticated user with the proper cookie will be able to read the session ensuring that if the server is somehow
     * compromised, the active session data wont be readable.
     *
     * @param string $savePath
     * @param string $sessionName
     * @throws \Exception
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        parent::open($savePath, $sessionName);
        $this->cookieKeyName = "key_$sessionName";
        if (!isset($_COOKIE[$this->cookieKeyName])) {
            $this->createEncryptionCookie();
            return true;
        }
        $this->encryptionKey = base64_decode($_COOKIE[$this->cookieKeyName]);
        return true;
    }

    /**
     * Overrides the session handler read method to make sure to properly decrypt the data contained within using the
     * encryption key store in the user's cookies.
     *
     * @param string $sessionId
     * @return string|null
     */
    public function read($sessionId)
    {
        $data = parent::read($sessionId);
        return (!$data) ? "" : Cryptography::decrypt(base64_decode($data), $this->encryptionKey);
    }

    /**
     * Overrides the session handler write method to make sure to properly encrypt the data before saving it using the
     * encryption key store in the user's cookies.
     *
     * @param string $sessionId
     * @param string $data
     * @return bool
     */
    public function write($sessionId, $data)
    {
        $data = Cryptography::encrypt($data, $this->encryptionKey);
        return parent::write($sessionId, base64_encode($data));
    }

    /**
     * Destroy session file on disk and delete encryption cookie if no session is active after deletion.
     *
     * @param string $sessionId
     * @return bool
     */
    public function destroy($sessionId)
    {
        parent::destroy($sessionId);
        if (isset($_COOKIE[$this->cookieKeyName])) {
            setcookie($this->cookieKeyName, '', 1);
            unset($_COOKIE[$this->cookieKeyName]);
        }
        return true;
    }

    /**
     * Sends the cookie with the encryption key.
     */
    private function createEncryptionCookie()
    {
        $this->encryptionKey = Cryptography::randomBytes(16);
        $cookieSettings = session_get_cookie_params();
        setcookie(
            $this->cookieKeyName,
            base64_encode($this->encryptionKey),
            ($cookieSettings['lifetime'] > 0) ? time() + $cookieSettings['lifetime'] : 0,
            $cookieSettings['path'],
            $cookieSettings['domain'],
            $cookieSettings['secure'],
            $cookieSettings['httponly']
        );
    }
}
