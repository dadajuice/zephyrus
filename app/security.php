<?php

use Zephyrus\Network\Request;
use Zephyrus\Security\ContentSecurityPolicy;
use Zephyrus\Security\IntrusionDetection;
use Zephyrus\Security\SecureHeader;
use Zephyrus\Application\Configuration;

if (Configuration::getSecurityConfiguration('ids_enabled')) {
    $ids = IntrusionDetection::getInstance();
    $ids->onDetection(function($data) {
        echo "IDS !";
        \Zephyrus\Security\SystemLog::addSecurity('IDS Detection : ' . json_encode($data));
        exit;
    });
}

$csp = new ContentSecurityPolicy();
$csp::generateNonce();
$csp->setDefaultSources(["'self'"]);
$csp->setFontSources(["'self'", 'https://fonts.googleapis.com', 'https://fonts.gstatic.com']);
$csp->setStyleSources(["'unsafe-inline'", "'self'", 'https://fonts.googleapis.com', 'http://zephyrus.local']);
$csp->setScriptSources(["'self'", 'https://ajax.googleapis.com', 'https://maps.googleapis.com', 'https://www.google-analytics.com', 'http://connect.facebook.net']);
$csp->setChildSources(["'self'", 'http://zephyrus.local', 'http://staticxx.facebook.com']);
$csp->setImageSources([
    "'self'", 'http://zephyrus.local',
    'data:', 'https://csi.gstatic.com', 'https://maps.gstatic.com',
    'https://maps.googleapis.com', 'https://www.google-analytics.com',
    'https://www.facebook.com']);
$csp->setBaseUri([Request::getBaseUrl()]);
//$csp->setReportUri('/utilities/csp-report');

$header = SecureHeader::getInstance();
$header->setContentSecurityPolicy($csp);