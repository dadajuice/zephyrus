<?php namespace Zephyrus\Tests\Security;

use PHPUnit\Framework\TestCase;
use Zephyrus\Security\CrossOriginResourcePolicy;

class CrossOriginResourcePolicyTest extends TestCase
{
    protected function tearDown(): void
    {
        header_remove(); // reset headers
    }

    public function testSend()
    {
        $cors = new CrossOriginResourcePolicy();
        $cors->setAccessControlAllowOrigin('*');
        self::assertEquals("*", $cors->getAccessControlAllowOrigin());
        $cors->send();
        self::assertTrue(in_array('Access-Control-Allow-Origin: *', xdebug_get_headers()));
    }

    public function testSendNothing()
    {
        $cors = new CrossOriginResourcePolicy(); // default empty
        $cors->send();
        self::assertTrue(empty(xdebug_get_headers()));
    }

    public function testSendAll()
    {
        $cors = new CrossOriginResourcePolicy();
        $cors->setAccessControlAllowOrigin('*');
        $cors->setAccessControlAllowCredentials("true");
        $cors->setAccessControlAllowExposeHeaders('X-SERVER');
        $cors->setAccessControlAllowHeaders('Accept, Content-Type');
        $cors->setAccessControlAllowMethods('GET, POST, DELETE, PATCH, PUT');
        $cors->setAccessControlMaxAge(7200);
        self::assertEquals("*", $cors->getAccessControlAllowOrigin());
        self::assertEquals("true", $cors->getAccessControlAllowCredentials());
        self::assertEquals("X-SERVER", $cors->getAccessControlAllowExposeHeaders());
        self::assertEquals("Accept, Content-Type", $cors->getAccessControlAllowHeaders());
        self::assertEquals("GET, POST, DELETE, PATCH, PUT", $cors->getAccessControlAllowMethods());
        self::assertEquals(7200, $cors->getAccessControlMaxAge());
        $cors->send();
        self::assertTrue(in_array('Access-Control-Allow-Origin: *', xdebug_get_headers()));
        self::assertTrue(in_array('Access-Control-Allow-Headers: Accept, Content-Type', xdebug_get_headers()));
        self::assertTrue(in_array('Access-Control-Allow-Methods: GET, POST, DELETE, PATCH, PUT', xdebug_get_headers()));
        self::assertTrue(in_array('Access-Control-Allow-Credentials: true', xdebug_get_headers()));
        self::assertTrue(in_array('Access-Control-Expose-Headers: X-SERVER', xdebug_get_headers()));
    }
}
