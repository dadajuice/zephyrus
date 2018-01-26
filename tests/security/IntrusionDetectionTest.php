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
        $ids = IntrusionDetection::getInstance(new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = array()) {}
        });
        $ids->setSurveillance(IntrusionDetection::GET);
        $_GET['test'] = "' AND 1=1#";
        $ids->run();
    }

    public function testMonitoring()
    {
        $ids = IntrusionDetection::getInstance(new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = array())
            {
                echo "works";
            }
        });
        $ids->setSurveillance(IntrusionDetection::GET | IntrusionDetection::POST | IntrusionDetection::REQUEST
            | IntrusionDetection::COOKIE);
        self::assertTrue($ids->isMonitoringCookie());
        self::assertTrue($ids->isMonitoringGet());
        self::assertTrue($ids->isMonitoringPost());
        self::assertTrue($ids->isMonitoringRequest());
    }

    public function testDetection()
    {
        $ids = IntrusionDetection::getInstance(new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = array())
            {
                echo "works";
            }
        });
        $ids->setSurveillance(IntrusionDetection::GET | IntrusionDetection::POST | IntrusionDetection::REQUEST
            | IntrusionDetection::COOKIE);
        $ids->onDetection(function ($data) {});
        $_GET['test'] = "' AND 1=1#";
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
        $ids = IntrusionDetection::getInstance(new class extends \Psr\Log\AbstractLogger {
            public function log($level, $message, array $context = array()) {}
        });
        $ids->setSurveillance(0);
        $_GET['test'] = "' AND 1=1#";
        $ids->run(function ($data) {});
    }
}