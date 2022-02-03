<?php namespace Zephyrus\Tests\Application\Session;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RangeException;
use Zephyrus\Application\Session;
use Zephyrus\Exceptions\SessionException;

class SessionExpirationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Make sure any previous session initiated in another test class will not interfere
        Session::getInstance()->destroy();
        Session::kill();
    }

    public function tearDown(): void
    {
        // After each test, properly destroy the session
        Session::getInstance()->destroy();
        Session::kill();
    }

    public function testDefaultValuesGetter()
    {
        $session = Session::getInstance();
        self::assertEquals(0, $session->getRefreshRate());
        self::assertEquals('none', $session->getRefreshMode());
    }

    public function testValuesGetter()
    {
        $session = Session::getInstance([
            'fingerprint_ua' => false,
            'refresh_mode' => 'request',
            'refresh_rate' => 50
        ]);
        self::assertEquals(50, $session->getRefreshRate());
        self::assertEquals('request', $session->getRefreshMode());
    }

    public function testRefreshByRequest()
    {
        $session = Session::getInstance([
            'fingerprint_ua' => false,
            'refresh_mode' => 'request',
            'refresh_rate' => 2
        ]);
        $session->start();
        self::assertEquals(2, $_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH']);

        $oldId = $session->getId();
        $session->start();
        self::assertEquals(1, $_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH']);
        $newId = $session->getId();
        self::assertEquals($oldId, $newId);

        $session->start();
        self::assertEquals(2, $_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH']);
        $newId = $session->getId();
        self::assertNotEquals($oldId, $newId);

        $oldId = $session->getId();
        $session->start();
        self::assertEquals(1, $_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH']);
        $newId = $session->getId();
        self::assertEquals($oldId, $newId);

        $session->start();
        self::assertEquals(2, $_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH']);
        $newId = $session->getId();
        self::assertNotEquals($oldId, $newId);
    }

    public function testRefreshByInterval()
    {
        $session = Session::getInstance([
            'fingerprint_ua' => false,
            'refresh_mode' => 'interval',
            'refresh_rate' => 2 // 2 seconds
        ]);
        $session->start();
        self::assertTrue(isset($_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP']));

        $oldId = $session->getId();
        $session->start();
        $newId = $session->getId();
        self::assertEquals($oldId, $newId); // Same second

        sleep(4); // should change next time

        $session->start();
        self::assertTrue(isset($_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP']));
        $newId = $session->getId();
        self::assertNotEquals($oldId, $newId);
    }

    public function testRefreshByProbabilityAlways()
    {
        $session = Session::getInstance([
            'fingerprint_ua' => false,
            'refresh_mode' => 'probability',
            'refresh_rate' => 100 // 100% chance to refresh
        ]);
        $session->start();
        $oldId = $session->getId();

        $session->start();
        $newId = $session->getId();
        self::assertNotEquals($oldId, $newId);
    }

    public function testRefreshByProbabilityNever()
    {
        $session = Session::getInstance([
            'fingerprint_ua' => false,
            'refresh_mode' => 'probability',
            'refresh_rate' => 0 // 0% chance to refresh
        ]);
        $session->start();
        $oldId = $session->getId();

        $session->start();
        $newId = $session->getId();
        self::assertEquals($oldId, $newId);
    }

    public function testInvalidRefreshMode()
    {
        self::expectException(SessionException::class);
        self::expectExceptionCode(SessionException::ERROR_INVALID_REFRESH_MODE);
        Session::getInstance([
            'refresh_mode' => 'wrong'
        ]);
    }

    public function testInvalidRefreshRate()
    {
        self::expectException(RangeException::class);
        Session::getInstance([
            'refresh_mode' => 'probability',
            'refresh_rate' => 500
        ]);
    }

    public function testInvalidRefreshRate2()
    {
        self::expectException(InvalidArgumentException::class);
        Session::getInstance([
            'refresh_mode' => 'request',
            'refresh_rate' => -40
        ]);
    }
}
