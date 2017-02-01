<?php

namespace Zephyrus\Application;

class SessionStorage
{
    const DEFAULT_SESSION_NAME = 'PHPSESSID';

    /**
     * @var array
     */
    private $content = [];

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \SessionHandler
     */
    private $handler = null;

    /**
     * @var bool
     */
    private $started = false;

    public function __construct($name)
    {
        $this->name = $name ?? self::DEFAULT_SESSION_NAME;
    }

    /**
     * @return string
     */
    public static function getSavePath()
    {
        return (!empty(session_save_path())) ? session_save_path() : sys_get_temp_dir();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->sessionId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    public function start()
    {
        session_name($this->name);
        if (!is_null($this->handler)) {
            session_set_save_handler($this->handler);
        }
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->sessionId = session_id();
        $this->content = &$_SESSION;
        $this->started = true;
    }

    public function destroy()
    {
        $_SESSION = [];
        setcookie($this->name, '', 1);
        unset($_COOKIE[session_name()]);
        session_destroy();
    }

    public function refresh()
    {
        session_regenerate_id(true);
        $this->sessionId = session_id();
    }

    /**
     * Restart the entire session by regenerating the identifier, deleting all
     * data and initiating handlers.
     */
    public function restart()
    {
        $this->destroy();
        $this->start();
    }

    /**
     * @return array
     */
    public function &getContent(): array
    {
        return $this->content;
    }

    /**
     * @param \SessionHandler $handler
     */
    protected function setHandler(\SessionHandler $handler)
    {
        $this->handler = $handler;
    }
}
