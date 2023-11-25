<?php

use Zephyrus\Application\Configuration;
use Zephyrus\Application\Feedback;
use Zephyrus\Application\Form;
use Zephyrus\Application\Localization;
use Zephyrus\Core\Session;
use Zephyrus\Security\ContentSecurityPolicy;
use Zephyrus\Utilities\StringUtility;

const FORMAT_DATE = "Y-m-d";
const FORMAT_TIME = "H:i:s";
const FORMAT_DATE_TIME = FORMAT_DATE . " " . FORMAT_TIME;

/**
 * Shorthand function to quickly access session data. If the given parameter is an associative array, it will set the
 * key / values to the current session. Otherwise, it will try to read the value.
 *
 * @param array|string $data
 * @param mixed $defaultValue
 * @return mixed|void
 */
function session(array|string $data, mixed $defaultValue = null)
{
    if (is_array($data)) {
        Session::setAll($data);
        return;
    }
    return Session::get($data, $defaultValue);
}

/**
 * Shorthand function to quickly access feedback error messages from anywhere including within Pug views. Retrieves the
 * error messages associated with the given field name. If there is no direct key matching the field name, it will try
 * to find key starting with the given name and join the messages. Useful to group pathing errors. E.g.
 *
 * 'firstname' => ['Must not be empty'],
 * 'amount' => ['Must be a number', 'Must be positive'],
 * 'cart[].quantity.2' => ['Must not be empty']
 * 'cart[].amount.2' => ['Must be a number']
 *
 * Feedback::read('firstname'); // ['Must not be empty']
 * Feedback::read('cart[]'); // ['Must not be empty', 'Must be a number']
 *
 * @param ?string $fieldName
 * @return array
 */
function feedback(?string $fieldName = null): array
{
    return is_null($fieldName)
        ? Feedback::readAll()
        : Feedback::read($fieldName);
}

/**
 * Shorthand function to quickly access feedback field names from anywhere including within Pug views. Retrieves the
 * field names which contains error messages. This will return the array notation instead of the pathing for easier html
 * manipulation. E.g. cart[].quantity.2 -> cart[quantity][2].
 *
 * @return array
 */
function feedbackFields(): array
{
    return Feedback::getFieldNames();
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
function format(string $type, ...$args): string
{
    $class = '\Zephyrus\Utilities\Formatter';
    return forward_static_call_array([$class, $type], $args);
}

/**
 * Simple alias function to directly retrieve a configuration property from the config.ini file to simplify usage within
 * view files.
 *
 * @param string $section
 * @param string|null $property
 * @param string|null $defaultValue
 * @return mixed
 */
function config(string $section, ?string $property = null, ?string $defaultValue = null): mixed
{
    $config = Configuration::read($section);
    return ($property) ? $config[$property] ?? $defaultValue : $config;
}

/**
 * Simple alias function to read a memorized form value to simplify usage inside view files.
 *
 * @param string $fieldId
 * @param mixed $defaultValue
 * @return mixed
 */
function val(string $fieldId, mixed $defaultValue = ""): mixed
{
    $raw = Form::getRawSavedField($fieldId, null);
    return $raw ?? Form::getSavedField($fieldId, $defaultValue);
}

/**
 * Simple alias function to quickly retrieve the current CSP nonce to be used for inline JavaScript.
 *
 * @return string
 */
function nonce(): string
{
    return ContentSecurityPolicy::nonce();
}

/**
 * Simple alias function to get the localize string corresponding to the desired key (e.g. messages.success.add_user).
 *
 * @param string $key
 * @param mixed ...$args
 * @return string
 */
function localize(string $key, ...$args): string
{
    return Localization::getInstance()->localize($key, $args);
}

/**
 * Shortcut function to convert anonymous objects into associative arrays.
 *
 * @param stdClass $object
 * @return array
 */
function objectToArray(stdClass $object): array
{
    return json_decode(json_encode($object), true);
}

/**
 * Replacement of the empty function that should be used which considers numeric values (0, 0.0, "0", etc.).
 *
 * @param mixed $value
 * @return bool
 */
function is_blank(mixed $value): bool
{
    return empty($value) && !is_numeric($value);
}

function ellipsis(?string $string, int $length = 50): string
{
    return StringUtility::ellipsis($string, $length);
}

function acronym(?string $string, int $length = 2): string
{
    return StringUtility::acronym($string, $length);
}

function mark(?string $string, ?string $search): string
{
    return StringUtility::mark($string, $search);
}

/**
 * Provides a replacement for getallheaders if on another web server than Apache.
 *
 * @see https://github.com/ralouphie/getallheaders
 */
if (!function_exists('getallheaders')) {

    /**
     * Retrieves all HTTP header key/values as an associative array for the current request.
     *
     * @return array
     */
    function getallheaders(): array
    {
        $headers = [];
        $copy_server = [
            'CONTENT_TYPE'   => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5'    => 'Content-Md5',
        ];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $key = substr($key, 5);
                if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                    $headers[$key] = $value;
                }
            } elseif (isset($copy_server[$key])) {
                $headers[$copy_server[$key]] = $value;
            }
        }
        if (!isset($headers['Authorization'])) {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $basic_pass = $_SERVER['PHP_AUTH_PW'] ?? '';
                $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }
        return $headers;
    }
}
