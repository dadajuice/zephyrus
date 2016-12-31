<?php

use Zephyrus\Network\Router;
use Zephyrus\Application\ClassLocator;

$router = new Router();
foreach (recursiveGlob('../app/Routes/*.php') as $file) {
    include($file);
}

$controllers = ClassLocator::getClassesInNamespace("Controllers");
foreach ($controllers as $controller) {
    $reflection = new \ReflectionClass($controller);
    if ($reflection->implementsInterface('Zephyrus\Application\Routable')) {
        call_user_func($controller .'::initializeRoutes', $router);
    }
}

$router->run();