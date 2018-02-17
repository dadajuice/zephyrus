<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Security\IntrusionDetection;

class IntrusionDetectionTest extends TestCase
{
    /**
     * @expectedException \Exception
     */
    public function testNoCallbackException()
    {
        $ids = IntrusionDetection::getInstance();
        $ids->setSurveillance(IntrusionDetection::GET);
        $_GET['test'] = "' AND 1=1#";
        $ids->run();
    }

    public function testMonitoring()
    {
        $ids = IntrusionDetection::getInstance();
        $ids->setSurveillance(IntrusionDetection::GET | IntrusionDetection::POST | IntrusionDetection::REQUEST
            | IntrusionDetection::COOKIE);
        self::assertTrue($ids->isMonitoringCookie());
        self::assertTrue($ids->isMonitoringGet());
        self::assertTrue($ids->isMonitoringPost());
        self::assertTrue($ids->isMonitoringRequest());
    }

    public function testDetection()
    {
        $ids = IntrusionDetection::getInstance();
        $ids->setSurveillance(IntrusionDetection::GET | IntrusionDetection::POST | IntrusionDetection::REQUEST
            | IntrusionDetection::COOKIE);
        $ids->onDetection(function ($data) {
            echo "works";
        });
        $_GET['test'] = "<script>document.cookie;</script>";
        ob_start();
        $ids->run();
        $test = ob_get_clean();
        self::assertTrue(strpos($test, "works") !== false);
    }

    /**
     * @expectedException \Exception
     */
    public function testNothingToMonitorException()
    {
        $ids = IntrusionDetection::getInstance();
        $ids->setSurveillance(0);
        $_GET['test'] = "' AND 1=1#";
        $ids->run(function ($data) {});
    }
}