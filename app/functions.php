<?php

/**
 * Redirect user to specified URL. Throws an HTTP "303 See Other" header
 * instead of the default 301. This indicates, more precisely, that the
 * response if elsewhere.
 *
 * @param string $url
 */
function redirect($url)
{
    header('HTTP/1.1 303 See Other');
    header('Location: ' . $url);
    exit();
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
    $result = '<script type="text/javascript" nonce="' . \Chahira\Security\SecureRouter::getRequestNonce() . '">';
    $result .= 'document.write("' . str_rot13($email) . '".replace(/[a-zA-Z]/g, function(c){return String.fromCharCode((c<="Z"?90:122)>=(c=c.charCodeAt(0)+13)?c:c-26);}));';
    $result .= '</script>';
    return $result;
}

/**
 * SSE message sending
 *
 * @param int $id
 * @param int $retry
 * @param array $data
 */
function sendMessage($id, $data, $retry = 1000) {
    echo "id: $id" . PHP_EOL;
    echo "retry: " . $retry . PHP_EOL;
    echo "data: " . json_encode($data) . PHP_EOL;
    echo PHP_EOL;
    ob_flush();
    flush();
}

function getPartialMonthName($index)
{
    $partialMonths = ['jan', 'fév', 'mar', 'avr', 'mai', 'jun', 'jui', 'aoû', 'sep', 'oct', 'nov', 'déc'];
    return $partialMonths[$index];
}

function getMonthName($index)
{
    $fullMonths = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    return $fullMonths[$index];
}

function success($message)
{
    $_SESSION['success'] = $message;
}

function warning($message)
{
    $_SESSION['warning'] = $message;
}

function info($message)
{
    $_SESSION['info'] = $message;
}