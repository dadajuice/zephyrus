<?php namespace Zephyrus\Core\Session\Handlers;

use SessionHandler;
use Zephyrus\Exceptions\Session\SessionPathNotExistException;
use Zephyrus\Exceptions\Session\SessionPathNotWritableException;
use Zephyrus\Utilities\FileSystem\Directory;

class DefaultSessionHandler extends SessionHandler
{
    /**
     * @throws SessionPathNotWritableException
     * @throws SessionPathNotExistException
     */
    public function open(string $path, string $name): bool
    {
        $this->isAvailable($path);
        return parent::open($path, $name);
    }

    public function close(): bool
    {
        return true;
    }

    /**
     * @throws SessionPathNotWritableException
     * @throws SessionPathNotExistException
     */
    public function isAvailable(string $path): bool
    {
        if (!Directory::exists($path)) {
            throw new SessionPathNotExistException($path);
        }
        // @codeCoverageIgnoreStart
        if (!Directory::isWritable($path)) {
            throw new SessionPathNotWritableException($path);
        }
        // @codeCoverageIgnoreEnd
        return true;
    }
}
