<?php

namespace Zephyrus\Application;

use Zephyrus\Network\Router;
use Zephyrus\Security\SessionStorage;

class Bootstrap
{
    public static function start()
    {
        self::initializeErrorReporting();
        self::initializeLocale();
        self::initializeSession();
    }

    public static function initializeRoutableControllers(Router $router)
    {
        $classLocator = new ClassLocator('Controllers');
        $controllers = $classLocator->getClasses();
        foreach ($controllers as $controller) {
            $reflection = new \ReflectionClass($controller);
            if ($reflection->implementsInterface('Zephyrus\Network\Routable')) {
                $controllerInstance = $reflection->newInstance($router);
                $controllerInstance->initializeRoutes();
            }
        }
    }

    public static function getHelperFunctionsPath()
    {
        return realpath(__DIR__ . '/../functions.php');
    }

    private static function initializeErrorReporting()
    {
        $dev = (Configuration::getApplicationConfiguration('env') == 'dev');
        ini_set('display_startup_errors', $dev);
        ini_set('display_errors', $dev);
        ini_set('error_log', ROOT_DIR . '/logs/errors.log');
    }

    private static function initializeSession()
    {
        $session = Session::getInstance();
        $storage = new SessionStorage(Configuration::getSessionConfiguration());
        $session->setSessionStorage($storage);
        Session::getInstance()->start();
    }

    private static function initializeLocale()
    {
        date_default_timezone_set(Configuration::getApplicationConfiguration('timezone'));
        $charset = Configuration::getApplicationConfiguration('charset');
        $locale = Configuration::getApplicationConfiguration('locale') . '.' . $charset;
        setlocale(LC_MESSAGES, $locale);
        setlocale(LC_TIME, $locale);
        setlocale(LC_CTYPE, $locale);
    }
}
