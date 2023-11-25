<?php namespace Zephyrus\Tests\Core\Session;

use PHPUnit\Framework\TestCase;
use Zephyrus\Core\Session;
use Zephyrus\Exceptions\Session\SessionRefreshRateException;
use Zephyrus\Exceptions\Session\SessionRefreshRateProbabilityException;
use Zephyrus\Exceptions\Session\SessionSupportedRefreshModeException;

class SessionIdentifierManagerTest extends TestCase
{
    public function testDefaultValuesGetter()
    {
        $session = new Session();
        self::assertEquals(0, $session->getIdentifierManager()->getRefreshRate());
        self::assertEquals('none', $session->getIdentifierManager()->getRefreshMode());
    }

    public function testValuesGetter()
    {
        $session = new Session([
            'refresh_mode' => 'request',
            'refresh_rate' => 50
        ]);
        self::assertEquals(50, $session->getIdentifierManager()->getRefreshRate());
        self::assertEquals('request', $session->getIdentifierManager()->getRefreshMode());
    }

    public function testRefreshByRequest()
    {
        $session = new Session([
            'refresh_mode' => 'request',
            'refresh_rate' => 2
        ]);
        $oldId = $session->start();
        self::assertEquals(2, $_SESSION['__ZF_SESSION_REQUESTS_BEFORE_REFRESH']);
        $this->assertEquals($oldId, $session->getId());
        session_write_close(); // Mimic request end

        $newId = $session->start();
        self::assertEquals(1, $_SESSION['__ZF_SESSION_REQUESTS_BEFORE_REFRESH']);
        self::assertEquals($oldId, $newId); // Should be same sess id ...
        session_write_close(); // Mimic request end

        $newId = $session->start();
        self::assertEquals(2, $_SESSION['__ZF_SESSION_REQUESTS_BEFORE_REFRESH']);
        self::assertNotEquals($oldId, $newId); // Should be new id ...
        $oldId = $session->getId();
        session_write_close(); // Mimic request end

        $newId = $session->start();
        self::assertEquals(1, $_SESSION['__ZF_SESSION_REQUESTS_BEFORE_REFRESH']);
        self::assertEquals($oldId, $newId);
        session_write_close(); // Mimic request end

        $newId = $session->start();
        self::assertEquals(2, $_SESSION['__ZF_SESSION_REQUESTS_BEFORE_REFRESH']);
        self::assertNotEquals($oldId, $newId);
        $session->destroy();
    }

    public function testRefreshByInterval()
    {
        $session = new Session([
            'refresh_mode' => 'interval',
            'refresh_rate' => 2 // 2 seconds
        ]);
        $oldId = $session->start();
        $this->assertTrue(isset($_SESSION['__ZF_SESSION_LAST_ACTIVITY_TIMESTAMP']));
        $this->assertEquals($oldId, $session->getId());
        session_write_close(); // Mimic request end

        $newId = $session->start();
        self::assertEquals($oldId, $newId); // Same second
        session_write_close(); // Mimic request end

        sleep(3); // Should change next time

        $newId = $session->start();
        self::assertTrue(isset($_SESSION['__ZF_SESSION_LAST_ACTIVITY_TIMESTAMP']));
        self::assertNotEquals($oldId, $newId);
        $session->destroy();
    }

    public function testRefreshByProbabilityAlways()
    {
        $session = new Session([
            'refresh_mode' => 'probability',
            'refresh_rate' => 100 // 100% chance to refresh
        ]);

        $session->start();
        $oldId = $session->getId();
        session_write_close(); // Mimic request end

        $session->start();
        $newId = $session->getId();
        self::assertNotEquals($oldId, $newId);
        $session->destroy();
    }

    public function testRefreshByProbabilityNever()
    {
        $session = new Session([
            'refresh_mode' => 'probability',
            'refresh_rate' => 0 // 0% chance to refresh
        ]);
        $session->start();
        $oldId = $session->getId();
        session_write_close(); // Mimic request end

        $session->start();
        $newId = $session->getId();
        self::assertEquals($oldId, $newId);
    }

    public function testInvalidRefreshMode()
    {
        $this->expectException(SessionSupportedRefreshModeException::class);
        $this->expectExceptionMessage("The specified session refresh mode [wrong] is invalid. Must be one of the following values 'none', 'probability', 'interval' or 'request'.");
        new Session([
            'refresh_mode' => 'wrong'
        ]);
    }

    public function testInvalidRefreshRate()
    {
        $this->expectException(SessionRefreshRateProbabilityException::class);
        $this->expectExceptionMessage("Refresh rate must be between 0 and 100 (percentage) for probability mode.");
        new Session([
            'refresh_mode' => 'probability',
            'refresh_rate' => 500
        ]);
    }

    public function testInvalidRefreshRate2()
    {
        $this->expectException(SessionRefreshRateException::class);
        $this->expectExceptionMessage("Session refresh rate must be positive int value.");
        new Session([
            'refresh_mode' => 'request',
            'refresh_rate' => -40
        ]);
    }
}
