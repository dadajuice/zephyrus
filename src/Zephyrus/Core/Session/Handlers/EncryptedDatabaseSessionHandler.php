<?php namespace Zephyrus\Core\Session\Handlers;

use Zephyrus\Core\Session\SessionEncryption;

class EncryptedDatabaseSessionHandler extends DatabaseSessionHandler
{
    private SessionEncryption $sessionEncryption;

    public function open(string $path, string $name): bool
    {
        $this->sessionEncryption = new SessionEncryption($name);
        return parent::open($path, $name);
    }

    public function read(string $id): string
    {
        return $this->sessionEncryption->decrypt(parent::read($id)) ?? "";
    }

    public function write(string $id, string $data): bool
    {
        return parent::write($id, $this->sessionEncryption->encrypt($data));
    }

    public function destroy(string $id): bool
    {
        $this->sessionEncryption->destroy();
        return parent::destroy($id);
    }
}
