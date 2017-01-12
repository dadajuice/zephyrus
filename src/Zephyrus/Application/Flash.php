<?php namespace Zephyrus\Application;

class Flash
{
    public static function error($message)
    {
        $_SESSION['__FLASH']['ERROR'] = $message;
    }

    public static function success($message)
    {
        $_SESSION['__FLASH']['SUCCESS'] = $message;
    }

    public static function warning($message)
    {
        $_SESSION['__FLASH']['WARNING'] = $message;
    }

    public static function info($message)
    {
        $_SESSION['__FLASH']['INFO'] = $message;
    }

    public static function notice($message)
    {
        $_SESSION['__FLASH']['NOTICE'] = $message;
    }

    public static function readAll()
    {
        $args = [];
        $args["flash"]["success"] = (isset($_SESSION['__FLASH']['SUCCESS'])) ? $_SESSION['__FLASH']['SUCCESS'] : "";
        $args["flash"]["warning"] = (isset($_SESSION['__FLASH']['WARNING'])) ? $_SESSION['__FLASH']['WARNING'] : "";
        $args["flash"]["error"] = (isset($_SESSION['__FLASH']['ERROR'])) ? $_SESSION['__FLASH']['ERROR'] : "";
        $args["flash"]["notice"] = (isset($_SESSION['__FLASH']['NOTICE'])) ? $_SESSION['__FLASH']['NOTICE'] : "";
        $args["flash"]["info"] = (isset($_SESSION['__FLASH']['INFO'])) ? $_SESSION['__FLASH']['INFO'] : "";
        self::clearAll();
        return $args;
    }

    private static function clearAll()
    {
        unset($_SESSION['__FLASH']);
    }
}
