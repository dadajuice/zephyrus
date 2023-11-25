<?php namespace Zephyrus\Tests\Utilities;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\OperatingSystem;

class OperatingSystemTest extends TestCase
{
    public function testName()
    {
        $results = OperatingSystem::getName();
        $this->assertTrue(property_exists($results, 'system'));
        $this->assertTrue(property_exists($results, 'release'));
        $this->assertTrue(property_exists($results, 'version'));
        $this->assertTrue(property_exists($results, 'machine'));
    }

    public function testDiskStats()
    {
        $results = OperatingSystem::getDiskStats("/");
        $this->assertTrue(property_exists($results, 'free'));
        $this->assertTrue(property_exists($results, 'total'));
        $this->assertTrue(property_exists($results, 'used'));
        $this->assertTrue(property_exists($results, 'percent'));
    }
}
