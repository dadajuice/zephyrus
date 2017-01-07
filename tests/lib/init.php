<?php namespace Zephyrus\Tests;

define('ROOT_DIR', __DIR__ . '/..');
require ROOT_DIR . '/../vendor/autoload.php';

use Zephyrus\Application\Bootstrap;

include(Bootstrap::getHelperFunctionsPath());
Bootstrap::start();