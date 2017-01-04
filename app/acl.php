<?php

use Zephyrus\Security\Authorization;

$auth = Authorization::getInstance();
$auth->setMode(Authorization::MODE_BLACKLIST);
$auth->addSessionRequirement('admin', 'AUTH_LEVEL', 'admin');
$auth->protect('/insert', Authorization::ALL, 'admin');

$_SESSION['AUTH_LEVEL'] = 'admin';