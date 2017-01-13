<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Cookie;

class CookieTest extends TestCase
{
    public function testSend()
    {
        //upgrade with tests including xdebug_get_headers set-cookie
        $cookie = new Cookie('test', '12345');
        $cookie->send();
        self::assertEquals('12345', $_COOKIE['test']);
        self::assertEquals('12345', $cookie->getValue());
        self::assertEquals('test', $cookie->getName());
        $_COOKIE = [];
    }

    public function testDestroy()
    {
        $cookie = new Cookie('bob', '12345');
        $cookie->send();
        self::assertEquals('12345', $_COOKIE['bob']);
        $cookie->destroy();
        self::assertFalse(isset($_COOKIE['bob']));
        $_COOKIE = [];
    }

    public function testParameters()
    {
        $cookie = new Cookie('bob');
        $cookie->setDomain('test.local');
        $cookie->setHttpOnly(false);
        $cookie->setIsValueUrlEncoded(true);
        $cookie->setLifetime(Cookie::DURATION_FOREVER);
        $cookie->setPath('/users');
        $cookie->setSecure(false);
        $cookie->setValue('testing');
        $cookie->send();
        self::assertEquals('testing', $_COOKIE['bob']);
        $_COOKIE = [];
    }
}