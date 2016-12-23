<?php

define('ROOT_DIR', __DIR__ . '/..');
require ROOT_DIR . '/vendor/autoload.php';

use Zephyrus\Application\Configuration;
use Zephyrus\Security\Session;

include('handlers.php');
include('zephyrus/functions.php');

$session = Session::getInstance(Configuration::getSessionConfiguration());
$session->start();

/*if (Configuration::getIdsConfiguration('active')) {
    $ids = IntrusionDetection::getInstance(Configuration::getIdsConfiguration());
    $ids->onDetection(function ($data) {
        Log::addSecurity("IDS detection : " . json_encode($data));
    });
}*/