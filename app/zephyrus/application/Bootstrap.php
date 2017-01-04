<?php namespace Zephyrus\Application;

use Zephyrus\Network\Router;

class Bootstrap
{
    public static function start()
    {
        date_default_timezone_set(Configuration::getApplicationConfiguration('timezone'));
        self::initializeErrorReporting();
        setlocale(LC_ALL, Configuration::getApplicationConfiguration('locale') . '.' . Configuration::getApplicationConfiguration('charset'));
        Session::getInstance()->start();
    }

    public static function initializeRoutableControllers(Router $router)
    {
        $controllers = ClassLocator::getClassesInNamespace("Controllers");
        foreach ($controllers as $controller) {
            $reflection = new \ReflectionClass($controller);
            if ($reflection->implementsInterface('Zephyrus\Application\Routable')) {
                call_user_func($controller .'::initializeRoutes', $router);
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
}