<?php namespace Zephyrus\Tests\Security\IntrusionDetection;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zephyrus\Exceptions\Security\IntrusionDetectionException;
use Zephyrus\Security\IntrusionDetection;
use Zephyrus\Tests\RequestUtility;

class IntrusionDetectionTest extends TestCase
{
    public function testWorking()
    {
        $request = RequestUtility::get("/?test=5");
        $ids = new IntrusionDetection($request);
        $ids->run();
        self::assertEquals("5", $request->getParameter('test'));
    }

    public function testWorkingWithArray()
    {
        $request = RequestUtility::get("/?test[]=3&test[]=4");
        $ids = new IntrusionDetection($request);
        $ids->run();
        self::assertEquals("3", $request->getParameter('test')[0]);
    }

    public function testDetectionInjection()
    {
        $this->expectException(IntrusionDetectionException::class);
        $request = RequestUtility::get("/?test=' AND 1=1#");
        $ids = new IntrusionDetection($request);
        $ids->run();
    }

    public function testDetectionInjectionArray()
    {
        $this->expectException(IntrusionDetectionException::class);
        $request = RequestUtility::get("/?test[]=3&test[]=4&test[]=' AND 1=1#");
        $ids = new IntrusionDetection($request);
        $ids->run();
    }

    public function testDetectionInjectionKeys()
    {
        $request = RequestUtility::get("/?test[]=3&test[]=4&test[]=' AND 1=1#");
        $ids = new IntrusionDetection($request);
        try {
            $ids->run();
        } catch (IntrusionDetectionException $e) {
            $intrusions = $e->getReport()->getDetectedIntrusions();
            self::assertEquals("parameters.test.2", $intrusions[0]->argument_name);
        }
    }

    public function testDetectionInjectionKeys2()
    {
        $request = RequestUtility::get("/?test[]=3&test[]=4&test[bob]=' AND 1=1#");
        $ids = new IntrusionDetection($request);
        try {
            $ids->run();
        } catch (IntrusionDetectionException $e) {
            $intrusions = $e->getReport()->getDetectedIntrusions();
            self::assertEquals("parameters.test.bob", $intrusions[0]->argument_name);
        }
    }

    public function testDetectionInjectionNestedArray()
    {
        $this->expectException(IntrusionDetectionException::class);
        $request = RequestUtility::get("/?test[]=3&test[]=4&test[greetings][]=hello&test[greetings][]=' AND 1=1#");
        $ids = new IntrusionDetection($request);
        $ids->run();
    }

    public function testDetectionInjectionWithUrl()
    {
        $request = RequestUtility::get("/<script>alert(document.cookie);</script>");
        $ids = new IntrusionDetection($request);
        try {
            $ids->run();
        } catch (IntrusionDetectionException $e) {
            $intrusions = $e->getReport()->getDetectedIntrusions();
            self::assertEquals("url.requested_url", $intrusions[0]->argument_name);
        }
    }

    public function testDetectionExceptionData()
    {
        $request = RequestUtility::get("/?test=' AND 1=1#");
        $ids = new IntrusionDetection($request);
        try {
            $ids->run();
        } catch (IntrusionDetectionException $e) {
            self::assertEquals(21, $e->getImpact());
            self::assertEquals("Detects chained SQL injection attempts 2/2", $e->getDetectedIntrusions()[0]->description);
            self::assertTrue($e->getReport()->getExecutionTime() > 0.0);
        }
    }

    public function testImpactThreshold()
    {
        $request = RequestUtility::get("/?test=' AND 1=1#");
        $ids = new IntrusionDetection($request, [
            'enabled' => true,
            'cached' => true,
            'impact_threshold' => 25,
            'monitor_cookies' => true,
            'exceptions' => []
        ]);
        $ids->run();
        self::assertEquals(21, $ids->getReport()->getImpact());
    }

    public function testImpactThresholdMisconfiguration()
    {
        $this->expectException(RuntimeException::class);
        $request = RequestUtility::get("/?test=' AND 1=1#");
        $ids = new IntrusionDetection($request, [
            'impact_threshold' => "oups"
        ]);
        $ids->run();
        self::assertEquals(3, $ids->getReport()->getImpact());
    }

    public function testNoCache()
    {
        $request = RequestUtility::get("/?test=' AND 1=1#");
        $ids = new IntrusionDetection($request, [
            'cached' => false
        ]);
        try {
            $ids->run();
        } catch (IntrusionDetectionException $e) {
            self::assertEquals(21, $e->getImpact());
            self::assertEquals("Detects chained SQL injection attempts 2/2", $e->getDetectedIntrusions()[0]->description);
            self::assertTrue($e->getReport()->getExecutionTime() > 0.0);
        }
    }
}
