<?php namespace Zephyrus\Tests\Network;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Cookie;

class CookieTest extends TestCase
{
    protected function tearDown(): void
    {
        $_COOKIE = [];
    }

    public function testDefaultSend()
    {
        $cookie = new Cookie('test_default_cookie', '12345');
        $cookie->send();
        self::assertEquals('12345', $_COOKIE['test_default_cookie']);
        self::assertEquals('12345', $cookie->getValue());
        self::assertEquals('test_default_cookie', $cookie->getName());

        $header = getSetCookieHeader("test_default_cookie");
        self::assertNotNull($header);

        $cookieContent = str_replace("Set-Cookie: ", "", $header);
        $cookieSegment = explode("; ", $cookieContent);
        self::assertEquals("test_default_cookie=12345", $cookieSegment[0]);
        self::assertEquals("path=/", $cookieSegment[3]);
        self::assertEquals("HttpOnly", $cookieSegment[4]);
        self::assertEquals("SameSite=Strict", $cookieSegment[5]);
    }

    public function testStaticRead()
    {
        $cookie = new Cookie('heroes', 'Batman');
        $cookie->send();
        self::assertEquals("Batman", Cookie::read('heroes'));
        self::assertEquals("Robin", Cookie::read('unavailable', 'Robin'));
    }

    public function testInvalidSameSite()
    {
        self::expectException(InvalidArgumentException::class);
        $cookie = new Cookie('same_site', 'bad_value');
        $cookie->setSameSite("invalid");
    }

    public function testDestroy()
    {
        $cookie = new Cookie('bob', '12345');
        $cookie->send();
        self::assertEquals('12345', $_COOKIE['bob']);
        $cookie->destroy();
        self::assertFalse(isset($_COOKIE['bob']));
    }

    public function testParameters()
    {
        $cookie = new Cookie('rolan');
        $cookie->setDomain('test.local');
        $cookie->setHttpOnly(false);
        $cookie->setIsValueUrlEncoded(true);
        $cookie->setLifetime(Cookie::DURATION_FOREVER);
        $cookie->setPath('/users');
        $cookie->setSecure(false);
        $cookie->setValue('testing');
        $cookie->setSameSite("Lax");
        $cookie->send();
        self::assertEquals('testing', $_COOKIE['rolan']);

        $header = getSetCookieHeader("rolan");
        self::assertNotNull($header);

        $cookieContent = str_replace("Set-Cookie: ", "", $header);
        $cookieSegment = explode("; ", $cookieContent);
        self::assertEquals("rolan=testing", $cookieSegment[0]);
        self::assertEquals("path=/users", $cookieSegment[3]);
        self::assertEquals("domain=test.local", $cookieSegment[4]);
        self::assertEquals("SameSite=Lax", $cookieSegment[5]);
    }
}