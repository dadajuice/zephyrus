<?php namespace Zephyrus\Security;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class SystemLog
{
    /**
     * @param string $message
     */
    public static function addSecurity($message)
    {
        $stream = new StreamHandler(ROOT_DIR . '/logs/security.log');
        $logger = new Logger('security');
        $logger->pushHandler($stream);
        $logger->addAlert($message);
    }

    /**
     * @param string $message
     */
    public static function addError($message)
    {
        $stream = new StreamHandler(ROOT_DIR . '/logs/errors.log');
        $logger = new Logger('errors');
        $logger->pushHandler($stream);
        $logger->addAlert($message);
    }

    /**
     * @param string $message
     */
    public static function addVerbose($message)
    {
        $stream = new StreamHandler(ROOT_DIR . '/logs/verbose.log');
        $logger = new Logger('verbose');
        $logger->pushHandler($stream);
        $logger->addInfo($message);
    }
}