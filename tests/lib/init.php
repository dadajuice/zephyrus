<?php namespace Zephyrus\Tests;

if (!@require __DIR__ . '/../../vendor/autoload.php') {
    die('You must set up the project dependencies, run composer install');
}

define('ROOT_DIR', __DIR__ . '/..');
require_once ROOT_DIR . '/../src/Zephyrus/functions.php';