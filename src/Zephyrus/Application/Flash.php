<?php

namespace Zephyrus\Application;

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
        $flash = Session::getInstance()->read('__FLASH');
        $args = [];
        $args['flash']['success'] = $flash['SUCCESS'] ?? '';
        $args['flash']['warning'] = $flash['WARNING'] ?? '';
        $args['flash']['error'] = $flash['ERROR'] ?? '';
        $args['flash']['notice'] = $flash['NOTICE'] ?? '';
        $args['flash']['info'] = $flash['INFO'] ?? '';
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
