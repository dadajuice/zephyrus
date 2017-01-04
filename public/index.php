<?php

use Zephyrus\Network\Router;
use Zephyrus\Application\Bootstrap;

$router = new Router();
foreach (recursiveGlob('../app/routes/*.php') as $file) {
    include($file);
}

Bootstrap::initializeRoutableControllers($router);
$router->run();