<?php namespace Zephyrus\Tests\Security\IntrusionDetection;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zephyrus\Exceptions\IntrusionDetectionException;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Security\IntrusionDetection;

class IntrusionDetectionTest extends TestCase
{
    public function testWorking()
    {
        $ids = new IntrusionDetection();
        RequestFactory::set(new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => 5
        ]]));
        $ids->run();
        self::assertEquals("5", RequestFactory::read()->getParameter('test'));
    }

    public function testDetectionInjection()
    {
        $this->expectException(IntrusionDetectionException::class);
        $ids = new IntrusionDetection();
        RequestFactory::set(new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => "' AND 1=1#"
        ]]));
        $ids->run();
    }

    public function testDetectionExceptionData()
    {
        $ids = new IntrusionDetection();
        RequestFactory::set(new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => "' AND 1=1#"
        ]]));
        try {
            $ids->run();
        } catch (IntrusionDetectionException $e) {
            self::assertEquals(3, $e->getImpact());
            self::assertEquals("Detects common comment types", $e->getDetectedIntrusions()[0]->description);
            self::assertTrue($e->getReport()->getExecutionTime() > 0.0);
        }
    }

    public function testImpactThreshold()
    {
        $ids = new IntrusionDetection([
            'enabled' => true,
            'cached' => true,
            'impact_threshold' => 10,
            'monitor_cookies' => true,
            'exceptions' => []
        ]);
        RequestFactory::set(new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => "' AND 1=1#"
        ]]));
        $report = $ids->run();
        self::assertEquals(3, $report->getImpact());
    }

    public function testImpactThresholdMisconfiguration()
    {
        $this->expectException(RuntimeException::class);
        $ids = new IntrusionDetection([
            'impact_threshold' => "oups"
        ]);
        RequestFactory::set(new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => "' AND 1=1#"
        ]]));
        $report = $ids->run();
        self::assertEquals(3, $report->getImpact());
    }

    public function testNoCache()
    {
        $ids = new IntrusionDetection([
            'cached' => false
        ]);
        RequestFactory::set(new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => "' AND 1=1#"
        ]]));
        try {
            $ids->run();
        } catch (IntrusionDetectionException $e) {
            self::assertEquals(3, $e->getImpact());
            self::assertEquals("Detects common comment types", $e->getDetectedIntrusions()[0]->description);
            self::assertTrue($e->getReport()->getExecutionTime() > 0.0);
        }
    }
}
