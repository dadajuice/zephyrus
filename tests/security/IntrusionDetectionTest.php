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
        $ids = new IntrusionDetection(new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = array())
            {
                throw new \Exception("detected");
            }
        });
        $ids->setSurveillance(IntrusionDetection::GET);
        $_GET['test'] = "' AND 1=1#";
        $ids->run();
    }

    public function testMonitoring()
    {
        $ids = new IntrusionDetection(new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = array())
            {
                throw new \Exception("detected");
            }
        });
        $ids->setSurveillance(IntrusionDetection::GET | IntrusionDetection::POST | IntrusionDetection::REQUEST
            | IntrusionDetection::COOKIE);
        self::assertTrue($ids->isMonitoringCookie());
        self::assertTrue($ids->isMonitoringGet());
        self::assertTrue($ids->isMonitoringPost());
        self::assertTrue($ids->isMonitoringRequest());
    }

    /**
     * @expectedException \Exception
     */
    public function testDetection()
    {
        $ids = new IntrusionDetection(new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = array())
            {
                throw new \Exception("detected");
            }
        });
        $ids->setSurveillance(IntrusionDetection::GET | IntrusionDetection::POST | IntrusionDetection::REQUEST
            | IntrusionDetection::COOKIE);
        $ids->onDetection(function ($data) {});
        $_GET['test'] = "' AND 1=1#";
        $ids->run();
    }

    /**
     * @expectedException \Exception
     */
    public function testNothingToMonitorException()
    {
        $ids = new IntrusionDetection(new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = array())
            {
                throw new \Exception("detected");
            }
        });
        $ids->setSurveillance(0);
        $_GET['test'] = "' AND 1=1#";
        $ids->run(function ($data) {});
    }
}