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
        $report = $monitor->run([
            'username' => "' AND 1=1#",
            'password' => "<script>document.cookie;</script>"
        ]);
        $events = $report->getDetectedIntrusions();
        self::assertCount(6, $events); // Detected 6 intrusions
        self::assertEquals(23, $report->getImpact());
        self::assertTrue(is_object($events[2]));
        self::assertEquals("Detects very basic XSS probings", $events[2]->description);
        self::assertTrue($report->getExecutionTime() > 0.0);
    }

    public function testNoDetection()
    {
        $rules = (new IntrusionRuleLoader())->loadFromFile();
        $monitor = new IntrusionMonitor($rules);
        $report = $monitor->run([
            'username' => "blewis",
            'password' => "passw0rd"
        ]);
        $events = $report->getDetectedIntrusions();
        self::assertCount(0, $events);
        self::assertEquals(0, $report->getImpact());
        self::assertTrue($report->getExecutionTime() > 0.0);
    }

    public function testDetectionException()
    {
        $rules = (new IntrusionRuleLoader())->loadFromFile();
        $monitor = new IntrusionMonitor($rules);
        $monitor->setExceptions(['password']); // Ignore password detection
        $report = $monitor->run([
            'username' => "' AND 1=1#",
            'password' => "<script>document.cookie;</script>"
        ]);
        $events = $report->getDetectedIntrusions();
        self::assertCount(1, $events);
        self::assertEquals(3, $report->getImpact());
        self::assertTrue(is_object($events[0]));
        self::assertEquals("Detects common comment types", $events[0]->description);
        self::assertTrue($report->getExecutionTime() > 0.0);
    }
}
