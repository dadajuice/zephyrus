<?php namespace Zephyrus\Tests;

if (!$loader = @require __DIR__ . '/../../vendor/autoload.php') {
    die('You must set up the project dependencies, run composer install');
}

// Simulate .htaccess default settings
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);

define('ROOT_DIR', realpath(__DIR__ . '/..'));
$loader->addPsr4('Controllers\\', ROOT_DIR . '/app/Controllers');
require_once ROOT_DIR . '/../src/Zephyrus/functions.php';

// Include functions specific for unit testing
require_once "helper_functions.php";
