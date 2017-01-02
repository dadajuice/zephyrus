<?php

define('ROOT_DIR', __DIR__ . '/..');
require ROOT_DIR . '/vendor/autoload.php';

use Zephyrus\Application\Configuration;
use Zephyrus\Application\Session;
//TODO: make two handlers : dev and prod
include('handlers.php');
include('zephyrus/functions.php');

setlocale(LC_ALL, Configuration::getApplicationConfiguration('locale') . '.' . Configuration::getApplicationConfiguration('charset'));
$session = Session::getInstance(Configuration::getSessionConfiguration());
$session->start();

include('security.php');