<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\OperatingSystem;

class OperatingSystemTest extends TestCase
{
    public function testName()
    {
        $results = OperatingSystem::getName();
        self::assertTrue(is_object($results));
    }

    public function testDiskStats()
    {
        $results = OperatingSystem::getDiskStats("/");
        self::assertTrue(is_object($results));
        OperatingSystem::getCpuAverageLoad();
        OperatingSystem::getMemoryUsage();
    }
}
