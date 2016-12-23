<?php namespace Zephyrus\Application;

class SessionHandler implements \SessionHandlerInterface
{
    /**
     * @var string Destination directory of session files
     */
    private $path;

    /**
     * @var string Session name as set with the session_name function
     */
    private $name;

    /**
     * Assigns session callback functions. This construct must be overridden by
     * child classes to reassign callbacks.
     */
    public function __construct()
    {
        session_set_save_handler(
            [$this, "open"],
            [$this, "close"],
            [$this, "read"],
            [$this, "write"],
            [$this, "destroy"],
            [$this, "gc"]
        );
    }

    /**
     * Called on session_start which instantiates the session destination an
     * name (randomly generated). Will throw an exception is the specified path
     * is not writable.
     *
     * @param string $savePath
     * @param string $sessionName
     * @return bool
     * @throws \Exception
     */
    public function open($savePath, $sessionName)
    {
        $this->path = $savePath . '/';
        if (!is_writable($this->path)) {
            throw new \RuntimeException("Session files directory [$savePath] is not writable");
        }
        $this->name = $sessionName;
        return true;
    }

    /**
     * This session handler doesn't really implement the close method since
     * everything is stored on file. Would be useful for database
     * implementations.
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * Write content to the session file associated with the specified id.
     *
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write($id, $data)
    {
        $sessionFilename = $this->getSessionFilename($id);
        $bytes = file_put_contents($sessionFilename, $data);
        return ($bytes !== false);
    }

    /**
     * Read all content from the session file associated with the specified
     * id. Must only returns the raw file content. Throws an exception if the
     * session file is not readable.
     *
     * @param string $id
     * @return bool | string
     */
    public function read($id)
    {
        $sessionFilename = $this->getSessionFilename($id);
        if (!is_readable($sessionFilename)) {
            return false;
        }
        return file_get_contents($sessionFilename);
    }

    /**
     * Remove the session file when session is destroyed.
     *
     * @param string $id
     * @return bool
     */
    public function destroy($id)
    {
        $sessionFilename = $this->getSessionFilename($id);
        if (file_exists($sessionFilename)) {
            @unlink($sessionFilename);
        }
        return true;
    }

    /**
     * Configure the PHP garbage collector deletion policy. Default is to
     * remove the session file when lifetime expires (about 24 minutes).
     *
     * @param int $lifetime
     * @return bool
     */
    public function gc($lifetime)
    {
        foreach (glob($this->path . 'sess_*') as $filename) {
            if (file_exists($filename) && filemtime($filename) + $lifetime < time()) {
                @unlink($filename);
            }
        }
        return true;
    }

    /**
     * Get the complete session file path including the configured directory
     * and generated id.
     *
     * @param string $id
     * @return string
     */
    protected function getSessionFilename($id)
    {
        return $this->path . "sess_$id";
    }
}