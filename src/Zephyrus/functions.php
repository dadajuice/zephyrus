<?php

use Zephyrus\Application\Configuration;
use Zephyrus\Application\Form;
use Zephyrus\Application\Localization;
use Zephyrus\Application\Session;
use Zephyrus\Security\ContentSecurityPolicy;

define('FORMAT_DATE', "Y-m-d");
define('FORMAT_TIME', "H:i:s");
define('FORMAT_DATE_TIME', FORMAT_DATE . " " . FORMAT_TIME);

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
    foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
        $files = array_merge($files, recursiveGlob($dir . '/' . basename($pattern), $flags));
    }
    return $files;
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
    $class = '\Zephyrus\Utilities\Formatter';
    return forward_static_call_array([$class, $type], $args);
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
 * Simple alias function to directly retrieve a configuration property from the config.ini file to simplify usage within
 * view files.
 *
 * @param string $section
 * @param string $property
 * @param string|null $defaultValue
 * @return mixed
 */
function config(string $section, string $property, string $defaultValue = null)
{
    return Configuration::getConfiguration($section, $property, $defaultValue);
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

/**
 * Simple alias function to get the localize string corresponding to the
 * desired key (e.g. messages.success.add_user).
 *
 * @param string $key
 * @param mixed ...$args
 * @return string
 */
function localize($key, ...$args): string
{
    return Localization::getInstance()->localize($key, $args);
}

/**
 * Simple alias function to sprintf parameters into the defined message.
 *
 * @param string $message
 * @param mixed ...$args
 * @return string
 */
function __(string $message, ...$args): string
{
    $parameters = array_merge([$message], $args);
    return call_user_func_array('sprintf', $parameters);
}
