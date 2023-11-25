<?php namespace Zephyrus\Tests\Utilities;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\RemoteServer;
use Zephyrus\Utilities\Validation;

class RemoteServerTest extends TestCase
{
    public function testAvailable()
    {
        $remote = new RemoteServer("github.com");
        self::assertTrue($remote->isServiceAvailable(443));
    }

    public function testDns()
    {
        $remote = new RemoteServer("github.com");
        self::assertEquals('github.com', $remote->getHostname());
        self::assertTrue(Validation::isIPv4($remote->getIpAddress()));
    }

    public function testDns2()
    {
        $remote = new RemoteServer("206.167.240.12");
        self::assertEquals(null, $remote->getHostname());
    }

    public function testSsl()
    {
        $remote = new RemoteServer("github.com");
        $date = $remote->getSslExpiration();
        self::assertTrue(Validation::isDateTime24Hours($date, true));
    }
}
