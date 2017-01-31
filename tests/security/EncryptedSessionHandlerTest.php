<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Security\EncryptedSessionHandler;

class EncryptedSessionHandlerTest extends TestCase
{
    public function testAll()
    {
        $handler = new EncryptedSessionHandler();
        session_set_save_handler($handler);
        session_name('phpsessid');
        session_start();
        $handler->open('/tmp', 'phpsessid');
        $headers = xdebug_get_headers();
        $headers = array_reverse($headers);
        $cookie = null;
        foreach ($headers as $header) {
            if (strpos($header, 'Set-Cookie: key_phpsessid') !== false) {
                $cookie = $header;
                $cookie = str_replace('Set-Cookie: key_phpsessid=', '', $cookie);
                $cookie = str_replace('; path=/', '', $cookie);
                break;
            }
        }
        $_COOKIE['key_phpsessid'] = urldecode($cookie);

        $id = session_id();
        $handler->write($id, 'secret');
        $result = $handler->read($id);
        self::assertEquals('secret', $result);

        $handler->close();
        $handler->open('/tmp', 'phpsessid');
        $result = $handler->read($id);
        self::assertEquals('secret', $result);

        $handler->destroy($id);
        session_destroy();
        unset($_COOKIE['key_phpsessid']);
    }
}