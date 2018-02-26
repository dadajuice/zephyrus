<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Exceptions\IntrusionDetectionException;
use Zephyrus\Security\IntrusionDetection;

class IntrusionDetectionTest extends TestCase
{
    public function testWorking()
    {
        $ids = IntrusionDetection::getInstance();
        $ids->setSurveillance(IntrusionDetection::GET | IntrusionDetection::POST | IntrusionDetection::REQUEST
            | IntrusionDetection::COOKIE);

        $_GET['test'] = "5";
        try {
            $ids->run();
        } catch (IntrusionDetectionException $exception) {
            throw $exception;
        }
        self::assertEquals("5", $_GET['test']);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\IntrusionDetectionException
     */
    public function testDetectionInjection()
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

    /**
     * @expectedException \Zephyrus\Exceptions\IntrusionDetectionException
     */
    public function testDetection()
    {
        $ids = IntrusionDetection::getInstance();
        $ids->setSurveillance(IntrusionDetection::GET | IntrusionDetection::POST | IntrusionDetection::REQUEST
            | IntrusionDetection::COOKIE);
        $_GET['test'] = "<script>document.cookie;</script>";
        try {
            $ids->run();
        } catch (IntrusionDetectionException $exception) {
            self::assertFalse(empty($exception->getIntrusionData()));
            throw $exception;
        }
    }

    /**
     * @expectedException \Exception
     */
    public function testNothingToMonitorException()
    {
        $ids = IntrusionDetection::getInstance();
        $ids->setSurveillance(0);
        $_GET['test'] = "' AND 1=1#";
        $ids->run();
    }
}