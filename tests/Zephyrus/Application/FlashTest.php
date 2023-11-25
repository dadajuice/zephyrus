<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Flash;
use Zephyrus\Core\Session;

class FlashTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $session = new Session();
        $session->start();
    }

    public static function tearDownAfterClass(): void
    {
        Session::destroy();
    }

    public function testError()
    {
        Flash::error("alert");
        $args = Flash::readAll();
        self::assertEquals("alert", $args->error);
        Flash::clearAll();
    }

    public function testWarning()
    {
        Flash::warning("warning");
        $args = Flash::readAll();
        self::assertEquals("warning", $args->warning);
        Flash::clearAll();
    }

    public function testSuccess()
    {
        Flash::success("success");
        $args = Flash::readAll();
        self::assertEquals("success", $args->success);
        Flash::clearAll();
    }

    public function testNotice()
    {
        Flash::notice("notice");
        $args = Flash::readAll();
        self::assertEquals("notice", $args->notice);
        Flash::clearAll();
    }

    public function testInfo()
    {
        Flash::info("info");
        $args = Flash::readAll();
        self::assertEquals("info", $args->info);
        Flash::clearAll();
    }
}