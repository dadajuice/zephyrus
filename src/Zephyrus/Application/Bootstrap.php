<?php namespace Zephyrus\Application;

use Zephyrus\Network\Router;

class Bootstrap
{
    public static function start()
    {
        self::initializeErrorReporting();
        self::initializeLocale();
    }

    public static function initializeRoutableControllers(Router $router)
    {
        foreach (recursiveGlob(ROOT_DIR . '/app/Controllers/*.php') as $file) {
            $reflection = self::fileToReflectionClass($file);
            if (!is_null($reflection)
                && $reflection->implementsInterface('Zephyrus\Network\Routable')
                && !$reflection->isAbstract()) {
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

    private static function initializeLocale()
    {
        date_default_timezone_set(Configuration::getApplicationConfiguration('timezone'));
        $charset = Configuration::getApplicationConfiguration('charset');
        $locale = Configuration::getApplicationConfiguration('locale') . '.' . $charset;
        setlocale(LC_MESSAGES, $locale);
        setlocale(LC_TIME, $locale);
        setlocale(LC_CTYPE, $locale);
    }

    private static function fileToReflectionClass(string $file): ?\ReflectionClass
    {
        $appPosition = strpos($file, '/app/');
        if ($appPosition !== false) {
            $file = substr($file, $appPosition + 5);
            $file = str_replace('../app/', '', $file);
            $file = str_replace(DIRECTORY_SEPARATOR, '\\', $file);
            $file = str_replace('.php', '', $file);
            return new \ReflectionClass($file);
        }
        return null;
    }
}
