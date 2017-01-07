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
        } else {
            list($this->cryptKey, $this->cryptAuth) = explode(':', $_COOKIE[$this->cookieKeyName]);
            $this->cryptKey = base64_decode($this->cryptKey);
            $this->cryptAuth = base64_decode($this->cryptAuth);
        }
        return true;
    }

    public function read($id)
    {
        $data = parent::read($id);
        if (!$data) {
            return "";
        } else {
            return $this->decrypt($data);
        }
    }

    public function write($id, $data)
    {
        $data = $this->encrypt($data);
        return parent::write($id, $data);
    }

    /**
     * Destroy session file on disk and delete encryption cookie if no session
     * is active after deletion.
     *
     * @param string $id
     * @return bool
     */
    public function destroy($id)
    {
        parent::destroy($id);
        if (empty(session_id())) {
            if (isset($_COOKIE[$this->cookieKeyName])) {
                setcookie($this->cookieKeyName, '', 1);
                unset($_COOKIE[$this->cookieKeyName]);
            }
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
            ($cookieSettings['lifetime'] > 0)
                ? time() + $cookieSettings['lifetime']
                : 0,
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
        list($iv, $cipher) = explode(':', $cipher);
        $iv = base64_decode($iv);
        $cipher = base64_decode($cipher);
        $hmac = hash_hmac('sha256', $iv . Cryptography::getEncryptionAlgorithm() . $cipher, $this->cryptAuth);
        return $hmac . ':' . base64_encode($iv) . ':' . base64_encode($cipher);
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
        list($hmac, $iv, $cipher) = explode(':', $data);
        $ivReal = base64_decode($iv);
        $cipherReal = base64_decode($cipher);
        $newHmac = hash_hmac('sha256', $ivReal . Cryptography::getEncryptionAlgorithm() . $cipherReal, $this->cryptAuth);
        if ($hmac !== $newHmac) {
            return false;
        }
        $decrypt = Cryptography::decrypt($iv . ':' . $cipher, $this->cryptKey);
        return $decrypt;
    }
}