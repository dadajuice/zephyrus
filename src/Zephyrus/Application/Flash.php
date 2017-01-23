<?php namespace Zephyrus\Application;

class Flash
{
    public static function error($message)
    {
        self::addFlash('ERROR', $message);
    }

    public static function success($message)
    {
        self::addFlash('SUCCESS', $message);
    }

    public static function warning($message)
    {
        self::addFlash('WARNING', $message);
    }

    public static function info($message)
    {
        self::addFlash('INFO', $message);
    }

    public static function notice($message)
    {
        self::addFlash('NOTICE', $message);
    }

    public static function readAll(): array
    {
        $args = [];
        $args["flash"]["success"] = $_SESSION['__FLASH']['SUCCESS'] ?? "";
        $args["flash"]["warning"] = $_SESSION['__FLASH']['WARNING'] ?? "";
        $args["flash"]["error"] = $_SESSION['__FLASH']['ERROR'] ?? "";
        $args["flash"]["notice"] = $_SESSION['__FLASH']['NOTICE'] ?? "";
        $args["flash"]["info"] = $_SESSION['__FLASH']['INFO'] ?? "";
        self::clearAll();
        return $args;
    }

    public static function clearAll()
    {
        Session::getInstance()->remove('__FLASH');
    }

    private static function addFlash($type, $message)
    {
        $flash = Session::getInstance()->read('__FLASH');
        if (is_null($flash)) {
            $flash = [];
        }
        $flash[$type] = $message;
        Session::getInstance()->set('__FLASH', $flash);
    }
}
