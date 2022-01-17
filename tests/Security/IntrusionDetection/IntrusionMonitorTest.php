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
            'password' => "<script>document.cookie;</script>",
            'name' => "Rolan Balesque"
        ]);
        $events = $report->getDetectedIntrusions();
        self::assertCount(8, $events); // Detected 8 intrusions
        self::assertEquals(35, $report->getImpact());
        self::assertTrue(is_object($events[2]));
        self::assertEquals("Detects chained SQL injection attempts 2/2", $events[2]->description);
        self::assertTrue($report->getExecutionTime() > 0.0);
        self::assertTrue($report->hasDetected('username'));
        self::assertTrue($report->hasDetected('password'));
        self::assertFalse($report->hasDetected('name'));
        self::assertEquals("Detects common comment types", $report->getDetectedIntrusions('username')[0]->description);
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
        self::assertCount(3, $events);
        self::assertEquals(15, $report->getImpact());
        self::assertTrue(is_object($events[0]));
        self::assertEquals("Detects common comment types", $events[0]->description);
        self::assertTrue($report->getExecutionTime() > 0.0);
    }
}
