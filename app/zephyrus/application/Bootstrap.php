<?php namespace Zephyrus\Application;

use Zephyrus\Network\Router;

class Bootstrap
{

    public static function start()
    {
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
}