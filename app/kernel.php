<?php

define('ROOT_DIR', __DIR__ . '/..');
require ROOT_DIR . '/vendor/autoload.php';

use Zephyrus\Application\Configuration;
use Zephyrus\Security\Session;
use Zephyrus\Network\Request;
use Zephyrus\Security\IntrusionDetection;
use Zephyrus\Security\SystemLog;

define('PAGE_MAX_ENTITIES', 50);
include('zephyrus/functions.php');

include('handlers.php');

//$session = Session::getInstance(Configuration::getSessionConfiguration());
//$session->start();

/*if (Configuration::getIdsConfiguration('active')) {
    $ids = IntrusionDetection::getInstance(Configuration::getIdsConfiguration());
    $ids->onDetection(function ($data) {
        Log::addSecurity("IDS detection : " . json_encode($data));
    });
}*/