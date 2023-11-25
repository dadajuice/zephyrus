<?php namespace Zephyrus\Core\Session\Handlers;

use Exception;
use Zephyrus\Core\Session\SessionEncryption;

class EncryptedDefaultSessionHandler extends DefaultSessionHandler
{
    private SessionEncryption $sessionEncryption;

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
        $this->sessionEncryption = new SessionEncryption($name);
        return parent::open($path, $name);
    }

    /**
     * Overrides the session handler read method to make sure to properly decrypt the data contained within using the
     * encryption key store in the user's cookies.
     *
     * @param string $id
     * @return string|false
     */
    public function read(string $id): string|false
    {
        return $this->sessionEncryption->decrypt(parent::read($id)) ?? false;
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
        return parent::write($id, $this->sessionEncryption->encrypt($data));
    }

    /**
     * Destroy session file on disk and delete encryption cookie if no session is active after deletion.
     *
     * @param string $id
     * @return bool
     */
    public function destroy(string $id): bool
    {
        $this->sessionEncryption->destroy();
        return parent::destroy($id);
    }
}