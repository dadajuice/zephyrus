<?php

$auth = \Zephyrus\Security\Authorization::getInstance();
$auth->setMode(\Zephyrus\Security\Authorization::MODE_BLACKLIST);
$auth->addSessionRequirement('admin', 'AUTH_LEVEL', 'admin');
$auth->protect('/insert', \Zephyrus\Security\Authorization::ALL, 'admin');

$_SESSION['AUTH_LEVEL'] = 'admin';