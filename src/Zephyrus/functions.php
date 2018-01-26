<?php

use Zephyrus\Application\Form;
use Zephyrus\Application\Session;
use Zephyrus\Security\ContentSecurityPolicy;

/**
 * Basic filtering to eliminate any tags and empty leading / trailing
 * characters.
 *
 * @param string $data
 * @return string
 */
function purify($data)
{
    return htmlspecialchars(trim(strip_tags($data)), ENT_QUOTES | ENT_HTML401, 'UTF-8');
}

/**
 * Securely print an email address in an HTML page without worrying about email
 * scrapper robots.
 *
 * @param string $email
 * @return string
 */
function secureEmail($email)
{
    $result = '<script type="text/javascript" nonce="' . ContentSecurityPolicy::getRequestNonce() . '">';
    $result .= 'document.write("' . str_rot13($email) . '".replace(/[a-zA-Z]/g, function(c){return String.fromCharCode((c<="Z"?90:122)>=(c=c.charCodeAt(0)+13)?c:c-26);}));';
    $result .= '</script>';
    return $result;
}

/**
 * Performs a normal glob pattern search, but enters directories recursively.
 *
 * @param string $pattern
 * @param int $flags
 * @return array
 */
function recursiveGlob($pattern, $flags = 0)
{
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
        $files = array_merge($files, recursiveGlob($dir . '/' . basename($pattern), $flags));
    }
    return $files;
}

/**
 * Sort a collection of objects naturally using a specified getter method.
 *
 * @param object[] $objects
 * @param string $getterMethod
 * @return object[]
 */
function naturalSort(array $objects, string $getterMethod = 'getNumber')
{
    $orderedResults = [];
    $numbers = [];
    foreach ($objects as $object) {
        $numbers[] = $object->{$getterMethod}();
    }
    natsort($numbers);
    $orderedKeys = array_keys($numbers);
    foreach ($orderedKeys as $index) {
        $orderedResults[] = $objects[$index];
    }
    return $orderedResults;
}

/**
 * Simple alias function to simplify formatting usage inside view files. Use the
 * following types : filesize, time, elapsed, datetime, date, percent, money and
 * decimal.
 *
 * @param string $type
 * @param array ...$args
 * @return string
 */
function format(string $type, ...$args)
{
    $class = '\Zephyrus\Application\Formatter';
    $typeMapping = [
        'filesize' => 'formatHumanFileSize',
        'time' => 'formatTime',
        'elapsed' => 'formatElapsedDateTime',
        'datetime' => 'formatDateTime',
        'date' => 'formatDate',
        'percent' => 'formatPercent',
        'money' => 'formatMoney',
        'decimal' => 'formatDecimal'
    ];
    return (array_key_exists($type, $typeMapping))
        ? forward_static_call_array([$class, $typeMapping[$type]], $args)
        : 'FORMAT TYPE [' . $type . '] NOT DEFINED !';
}

/**
 * Simple alias function to directly read a data from the session to simplify
 * usage inside view files.
 *
 * @param string $key
 * @param mixed $defaultValue
 * @return mixed
 */
function sess(string $key, $defaultValue = null)
{
    return Session::getInstance()->read($key, $defaultValue);
}

/**
 * Simple alias function to read a memorized form value to simplify usage
 * inside view files.
 *
 * @param string $fieldId
 * @param mixed $defaultValue
 * @return mixed
 */
function val(string $fieldId, $defaultValue = "")
{
    return Form::readMemorizedValue($fieldId, $defaultValue);
}

/**
 * Simple alias function to quickly retrieve the CSP nonce to be used for
 * inline JavaScript.
 *
 * @return string
 */
function nonce(): string
{
    return ContentSecurityPolicy::getRequestNonce();
}
