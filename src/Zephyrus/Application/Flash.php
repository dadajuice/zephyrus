<?php namespace Zephyrus\Application;

use stdClass;
use Zephyrus\Core\Session;

class Flash
{
    private const SESSION_KEY = '__ZF_FLASH';

    public static function error(string|array $message): void
    {
        self::addFlash('ERROR', $message);
    }

    public static function success(string|array $message): void
    {
        self::addFlash('SUCCESS', $message);
    }

    public static function warning(string|array $message): void
    {
        self::addFlash('WARNING', $message);
    }

    public static function info(string|array $message): void
    {
        self::addFlash('INFO', $message);
    }

    public static function notice(string|array $message): void
    {
        self::addFlash('NOTICE', $message);
    }

    public static function readAll(): stdClass
    {
        $flash = Session::get(self::SESSION_KEY);
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
        Session::remove(self::SESSION_KEY);
    }

    private static function addFlash(string $type, string|array $message): void
    {
        $flash = Session::get(self::SESSION_KEY);
        if (is_null($flash)) {
            $flash = [];
        }
        $flash[$type] = $message;
        Session::set(self::SESSION_KEY, $flash);
    }
}
