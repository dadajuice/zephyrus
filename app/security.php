<?php

use Zephyrus\Network\Request;
use Zephyrus\Security\ContentSecurityPolicy;
use Zephyrus\Security\SecureHeader;

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