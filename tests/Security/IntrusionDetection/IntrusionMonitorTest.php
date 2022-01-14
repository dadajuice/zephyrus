<?php namespace Zephyrus\Tests\Security\IntrusionDetection;

use PHPUnit\Framework\TestCase;
use Zephyrus\Security\IntrusionDetection\IntrusionMonitor;
use Zephyrus\Security\IntrusionDetection\IntrusionRuleLoader;

class IntrusionMonitorTest extends TestCase
{
    public function testDetection()
    {
        $rules = (new IntrusionRuleLoader())->loadFromFile();
        $monitor = new IntrusionMonitor($rules);
        $impact = $monitor->run([
            'username' => "' AND 1=1#",
            'password' => "<script>document.cookie;</script>"
        ]);
        $reports = $monitor->getReports();
        self::assertCount(6, $reports); // Detected 6 intrusions
        self::assertEquals(23, $impact);
        self::assertEquals(23, $monitor->getImpact());
        self::assertTrue(is_object($reports[2]));
        self::assertEquals("Detects very basic XSS probings", $reports[2]->description);
    }

    public function testNoDetection()
    {
        $rules = (new IntrusionRuleLoader())->loadFromFile();
        $monitor = new IntrusionMonitor($rules);
        $impact = $monitor->run([
            'username' => "blewis",
            'password' => "passw0rd"
        ]);
        $reports = $monitor->getReports();
        self::assertCount(0, $reports);
        self::assertEquals(0, $impact);
        self::assertEquals(0, $monitor->getImpact());
    }
}
