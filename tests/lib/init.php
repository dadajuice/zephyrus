<?php namespace Zephyrus\Tests;

if (!$loader = @require __DIR__ . '/../../vendor/autoload.php') {
    die('You must set up the project dependencies, run composer install');
}

define('ROOT_DIR', __DIR__ . '/..');
$loader->addPsr4('Controllers\\', ROOT_DIR . '/app/Controllers');
require_once ROOT_DIR . '/../src/Zephyrus/functions.php';