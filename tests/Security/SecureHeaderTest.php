<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Security\ContentSecurityPolicy;
use Zephyrus\Security\CrossOriginResourcePolicy;
use Zephyrus\Security\SecureHeader;

class SecureHeaderTest extends TestCase
{
    public function testFrameOptions()
    {
        $header = new SecureHeader();
        $header->setFrameOptions("SAMEORIGIN");
        self::assertEquals("SAMEORIGIN", $header->getFrameOptions());
        $header->send();
        self::assertTrue(in_array('X-Frame-Options: SAMEORIGIN', xdebug_get_headers()));
    }

    public function testContentTypeOptions()
    {
        $header = new SecureHeader();
        $header->setContentTypeOptions("nosniff");
        self::assertEquals("nosniff", $header->getContentTypeOptions());
        $header->send();
        self::assertTrue(in_array('X-Content-Type-Options: nosniff', xdebug_get_headers()));
    }

    public function testStrictTransport()
    {
        $header = new SecureHeader();
        $header->setStrictTransportSecurity("max-age=16070400; includeSubDomains");
        self::assertEquals("max-age=16070400; includeSubDomains", $header->getStrictTransportSecurity());
        $header->send();
        self::assertTrue(in_array('Strict-Transport-Security: max-age=16070400; includeSubDomains', xdebug_get_headers()));
    }

    public function testContentSecurityPolicy()
    {
        $csp = new ContentSecurityPolicy();
        $csp->setDefaultSources([ContentSecurityPolicy::SELF]);
        $header = new SecureHeader();
        $header->setContentSecurityPolicy($csp);
        self::assertEquals([ContentSecurityPolicy::SELF], $header->getContentSecurityPolicy()->getAllHeader()['default-src']);
        $header->send();
        self::assertTrue(in_array("Content-Security-Policy: default-src 'self';", xdebug_get_headers()));
    }

    public function testAccessControl()
    {
        $cors = new CrossOriginResourcePolicy();
        $cors->setAccessControlAllowOrigin('dummy@domain.com');
        $header = new SecureHeader();
        $header->setCrossOriginResourcePolicy($cors);
        self::assertEquals('dummy@domain.com', $header->getCrossOriginResourcePolicy()->getAccessControlAllowOrigin());
        $header->send();
        self::assertTrue(in_array('Access-Control-Allow-Origin: dummy@domain.com', xdebug_get_headers()));
    }
}