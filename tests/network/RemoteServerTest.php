<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\RemoteServer;
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
        $remote = new RemoteServer("cegepst.qc.ca");
        self::assertTrue(!empty($remote->getDnsRecord(DNS_A)[0]['host']));
        self::assertEquals('cegepst.qc.ca', $remote->getHostname());
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
