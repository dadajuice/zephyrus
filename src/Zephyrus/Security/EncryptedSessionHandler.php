<?php namespace Zephyrus\Security;

class EncryptedSessionHandler extends \SessionHandler
{
    /**
     * @var string Encryption symmetric key created using the specified algorithm
     */
    private $cryptKey;

    /**
     * @var string HMac hash authentication key
     */
    private $cryptAuth;

    /**
     * @var string Cookie name that will store the encryption data (hmac and
     * symmetric key).
     */
    private $cookieKeyName;

    /**
     * Called on session_start, this method create the
     *
     * @param string $savePath
     * @param string $sessionName
     * @return bool
     * @throws \Exception
     */
    public function open($savePath, $sessionName)
    {
        parent::open($savePath, $sessionName);
        $this->cookieKeyName = "key_$sessionName";
        if (empty($_COOKIE[$this->cookieKeyName]) || strpos($_COOKIE[$this->cookieKeyName], ':') === false) {
            $this->createEncryptionCookie();
            return true;
        }
        list($this->cryptKey, $this->cryptAuth) = explode(':', $_COOKIE[$this->cookieKeyName]);
        $this->cryptKey = base64_decode($this->cryptKey);
        $this->cryptAuth = base64_decode($this->cryptAuth);
        return true;
    }

    public function read($sessionId)
    {
        $data = parent::read($sessionId);
        return (!$data) ? "" : $this->decrypt($data);
    }

    public function write($sessionId, $data)
    {
        $data = $this->encrypt($data);
        return parent::write($sessionId, $data);
    }

    /**
     * Destroy session file on disk and delete encryption cookie if no session
     * is active after deletion.
     *
     * @param string $sessionId
     * @return bool
     */
    public function destroy($sessionId)
    {
        parent::destroy($sessionId);
        if (empty(session_id()) && isset($_COOKIE[$this->cookieKeyName])) {
            setcookie($this->cookieKeyName, '', 1);
            unset($_COOKIE[$this->cookieKeyName]);
        }
        return true;
    }

    /**
     * @throws \Exception
     */
    private function createEncryptionCookie()
    {
        $keyLength = Cryptography::getEncryptionIvLength();
        $this->cryptKey = Cryptography::randomBytes($keyLength);
        $this->cryptAuth = Cryptography::randomBytes(32);
        $cookieSettings = session_get_cookie_params();
        setcookie(
            $this->cookieKeyName,
            base64_encode($this->cryptKey) . ':' . base64_encode($this->cryptAuth),
            ($cookieSettings['lifetime'] > 0) ? time() + $cookieSettings['lifetime'] : 0,
            $cookieSettings['path'],
            $cookieSettings['domain'],
            $cookieSettings['secure'],
            $cookieSettings['httponly']
        );
    }

    /**
     * Encrypt the specified data using the defined algorithm. Also create an
     * Hmac authentication hash.
     *
     * @param string $data
     * @return string
     */
    private function encrypt(string $data): string
    {
        $cipher = Cryptography::encrypt($data, $this->cryptKey);
        list($initializationVector, $cipher) = explode(':', $cipher);
        $initializationVector = base64_decode($initializationVector);
        $cipher = base64_decode($cipher);
        $content = $initializationVector . Cryptography::getEncryptionAlgorithm() . $cipher;
        $hmac = hash_hmac('sha256', $content, $this->cryptAuth);
        return $hmac . ':' . base64_encode($initializationVector) . ':' . base64_encode($cipher);
    }

    /**
     * Decrypt the specified data using the defined algorithm. Also verify the
     * Hmac authentication hash. Returns false if Hmac validation fails.
     *
     * @param string $data
     * @return string|bool
     */
    private function decrypt(string $data): string
    {
        list($hmac, $initializationVector, $cipher) = explode(':', $data);
        $ivReal = base64_decode($initializationVector);
        $cipherReal = base64_decode($cipher);
        $validHash = $ivReal . Cryptography::getEncryptionAlgorithm() . $cipherReal;
        $newHmac = hash_hmac('sha256', $validHash, $this->cryptAuth);
        if ($hmac !== $newHmac) {
            return false;
        }
        $decrypt = Cryptography::decrypt($initializationVector . ':' . $cipher, $this->cryptKey);
        return $decrypt;
    }
}
