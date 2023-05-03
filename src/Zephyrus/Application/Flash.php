<?php namespace Zephyrus\Application;

use stdClass;

class Flash
{
    private const SESSION_KEY = '__ZF_FLASH';

    public static function error(string $message): void
    {
        self::addFlash('ERROR', $message);
    }

    public static function success(string $message): void
    {
        self::addFlash('SUCCESS', $message);
    }

    public static function warning(string $message): void
    {
        self::addFlash('WARNING', $message);
    }

    public static function info(string $message): void
    {
        self::addFlash('INFO', $message);
    }

    public static function notice(string $message): void
    {
        self::addFlash('NOTICE', $message);
    }

    public static function readAll(): stdClass
    {
        $flash = Session::getInstance()->read(self::SESSION_KEY);
        $args = [];
        $args["success"] = $flash['SUCCESS'] ?? "";
        $args["warning"] = $flash['WARNING'] ?? "";
        $args["error"] = $flash['ERROR'] ?? "";
        $args["notice"] = $flash['NOTICE'] ?? "";
        $args["info"] = $flash['INFO'] ?? "";
        return (object) $args;
    }

    public static function clearAll(): void
    {
        Session::getInstance()->remove(self::SESSION_KEY);
    }

    private static function addFlash(string $type, string $message): void
    {
        $flash = Session::getInstance()->read(self::SESSION_KEY);
        if (is_null($flash)) {
            $flash = [];
        }
        $flash[$type] = $message;
        Session::getInstance()->set(self::SESSION_KEY, $flash);
    }
}
