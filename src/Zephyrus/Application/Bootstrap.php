<?php namespace Zephyrus\Application;

use ReflectionClass;
use ReflectionException;
use Zephyrus\Network\Router\RouteRepository;
use Zephyrus\Utilities\FileSystem\Directory;

class Bootstrap
{
    public static function getHelperFunctionsPath(): string
    {
        return realpath(__DIR__ . '/../functions.php');
    }

    public static function initializeControllerRoutes(RouteRepository $repository): void
    {
        foreach (Directory::recursiveGlob(ROOT_DIR . '/app/Controllers/*.php') as $file) {
            $reflection = self::fileToReflectionClass($file);
            if ($reflection->isSubclassOf('Zephyrus\Application\Controller') && !$reflection->isAbstract()) {
                call_user_func($reflection->getName() .'::initializeRoutes', $repository);
            }
        }
    }

    /**
     * Builds a ReflectionClass instance from a given file path. Should normally be a controller instance in this
     * context.
     *
     * @param string $file
     * @throws ReflectionException
     * @return ReflectionClass
     */
    private static function fileToReflectionClass(string $file): ReflectionClass
    {
        $appPosition = strpos($file, '/app/');
        $file = substr($file, $appPosition + 5);
        $file = str_replace('../app/', '', $file);
        $file = str_replace(DIRECTORY_SEPARATOR, '\\', $file);
        $file = str_replace('.php', '', $file);
        return new ReflectionClass($file);
    }
}
