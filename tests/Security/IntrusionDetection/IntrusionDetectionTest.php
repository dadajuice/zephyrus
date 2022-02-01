<?php namespace Zephyrus\Tests\Security\IntrusionDetection;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zephyrus\Exceptions\IntrusionDetectionException;
use Zephyrus\Network\Request;
use Zephyrus\Security\IntrusionDetection;

class IntrusionDetectionTest extends TestCase
{
    public function testWorking()
    {
        $request = new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => 5
        ]]);
        $ids = new IntrusionDetection($request);
        $ids->run();
        self::assertEquals("5", $request->getParameter('test'));
    }

    public function testWorkingWithArray()
    {
        $request = new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => [3, 4]
        ]]);
        $ids = new IntrusionDetection($request);
        $ids->run();
        self::assertEquals("3", $request->getParameter('test')[0]);
    }

    public function testDetectionInjection()
    {
        $this->expectException(IntrusionDetectionException::class);
        $request = new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => "' AND 1=1#"
        ]]);
        $ids = new IntrusionDetection($request);
        $ids->run();
    }

    public function testDetectionInjectionArray()
    {
        $this->expectException(IntrusionDetectionException::class);
        $request = new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => [4, 5, "' AND 1=1#"]
        ]]);
        $ids = new IntrusionDetection($request);
        $ids->run();
    }

    public function testDetectionInjectionKeys()
    {
        $request = new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => [4, 5, "' AND 1=1#"]
        ]]);
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
        $request = new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => [4, 5, 'bob' => "' AND 1=1#"]
        ]]);
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
        $request = new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => [4, 5, 'greetings' => ["hello", "' AND 1=1#"]]
        ]]);
        $ids = new IntrusionDetection($request);
        $ids->run();
    }

    public function testDetectionInjectionWithUrl()
    {
        $request = new Request("http://dummy.com/<script>alert(document.cookie);</script>", "GET");
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
        $request = new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => "' AND 1=1#"
        ]]);
        $ids = new IntrusionDetection($request);
        try {
            $ids->run();
        } catch (IntrusionDetectionException $e) {
            self::assertEquals(15, $e->getImpact());
            self::assertEquals("Detects common comment types", $e->getDetectedIntrusions()[0]->description);
            self::assertTrue($e->getReport()->getExecutionTime() > 0.0);
        }
    }

    public function testImpactThreshold()
    {
        $request = new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => "' AND 1=1#"
        ]]);
        $ids = new IntrusionDetection($request, [
            'enabled' => true,
            'cached' => true,
            'impact_threshold' => 20,
            'monitor_cookies' => true,
            'exceptions' => []
        ]);
        $ids->run();
        self::assertEquals(15, $ids->getReport()->getImpact());
    }

    public function testImpactThresholdMisconfiguration()
    {
        $this->expectException(RuntimeException::class);
        $request = new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => "' AND 1=1#"
        ]]);
        $ids = new IntrusionDetection($request, [
            'impact_threshold' => "oups"
        ]);
        $ids->run();
        self::assertEquals(3, $ids->getReport()->getImpact());
    }

    public function testNoCache()
    {
        $request = new Request("http://dummy.com", "GET", ['parameters' => [
            'test' => "' AND 1=1#"
        ]]);
        $ids = new IntrusionDetection($request, [
            'cached' => false
        ]);
        try {
            $ids->run();
        } catch (IntrusionDetectionException $e) {
            self::assertEquals(15, $e->getImpact());
            self::assertEquals("Detects common comment types", $e->getDetectedIntrusions()[0]->description);
            self::assertTrue($e->getReport()->getExecutionTime() > 0.0);
        }
    }
}
