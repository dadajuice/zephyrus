<?php namespace Zephyrus\Application;

use Zephyrus\Network\Router;

class Bootstrap
{
    public static function start()
    {
        date_default_timezone_set(Configuration::getApplicationConfiguration('timezone'));
        ini_set('post_max_size', Configuration::getApplicationConfiguration('upload_max_size'));
        ini_set('upload_max_filesize', Configuration::getApplicationConfiguration('upload_max_size'));
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
        /*ini_set('html_errors', 1);
        ini_set('log_errors', 1);
        ini_set('ignore_repeated_errors', 1);
        ini_set('ignore_repeated_source', 1);
        ini_set('report_memleaks', 1);
        ini_set('track_errors', 1);
        ini_set('docref_root', 0);
        ini_set('docref_ext', 0);*/
        ini_set('error_log', ROOT_DIR . '/logs/errors.log');
        //ini_set('error_reporting', -1);
        //ini_set('log_errors_max_len', 0);
    }
}