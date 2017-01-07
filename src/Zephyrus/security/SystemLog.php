<?php namespace Zephyrus\Security;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class SystemLog
{
    /**
     * @var Logger
     */
    private static $securityLogger;

    /**
     * @var Logger
     */
    private static $errorsLogger;

    /**
     * @var Logger
     */
    private static $verboseLogger;

    /**
     * @param string $message
     */
    public static function addSecurity($message)
    {
        self::buildSecurityLogger();
        self::$securityLogger->addAlert($message);
    }

    /**
     * @param string $message
     */
    public static function addError($message)
    {
        self::buildErrorsLogger();
        self::$errorsLogger->addAlert($message);
    }

    /**
     * @param string $message
     */
    public static function addVerbose($message)
    {
        self::buildVerboseLogger();
        self::$verboseLogger->addInfo($message);
    }

    /**
     * @return Logger
     */
    public static function getSecurityLogger()
    {
        self::buildSecurityLogger();
        return self::$securityLogger;
    }

    /**
     * @return Logger
     */
    public static function getErrorsLogger()
    {
        self::buildErrorsLogger();
        return self::$errorsLogger;
    }

    /**
     * @return Logger
     */
    public static function getVerboseLogger()
    {
        self::buildVerboseLogger();
        return self::$verboseLogger;
    }

    private static function buildSecurityLogger()
    {
        if (is_null(self::$securityLogger)) {
            $output = "[%datetime%] %message%\n";
            $formatter = new LineFormatter($output);
            $securityStream = new StreamHandler(ROOT_DIR . '/logs/security.log', Logger::WARNING);
            $securityStream->setFormatter($formatter);
            self::$securityLogger = new Logger('security');
            self::$securityLogger->pushHandler($securityStream);
        }
    }

    private static function buildErrorsLogger()
    {
        if (is_null(self::$securityLogger)) {
            $output = "[%datetime%] %level_name% : %message%\n";
            $formatter = new LineFormatter($output);
            $errorsStream = new StreamHandler(ROOT_DIR . '/logs/errors.log', Logger::INFO);
            $errorsStream->setFormatter($formatter);
            self::$errorsLogger = new Logger('errors');
            self::$errorsLogger->pushHandler($errorsStream);
        }
    }

    private static function buildVerboseLogger()
    {
        if (is_null(self::$securityLogger)) {
            $output = "[%datetime%] %level_name% : %message%\n";
            $formatter = new LineFormatter($output);
            $verboseStream = new StreamHandler(ROOT_DIR . '/logs/verbose.log', Logger::DEBUG);
            $verboseStream->setFormatter($formatter);
            self::$verboseLogger = new Logger('verbose');
            self::$verboseLogger->pushHandler($verboseStream);
        }
    }
}