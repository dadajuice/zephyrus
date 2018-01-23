<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Session;

class FlashTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        $session = Session::getInstance();
        $session->start();
    }

    public static function tearDownAfterClass()
    {
        $session = Session::getInstance();
        $session->destroy();
        Session::kill();
    }

    public function testError()
    {
        Flash::error("alert");
        $args = Flash::readAll();
        self::assertEquals("alert", $args['flash']['error']);
    }

    public function testWarning()
    {
        Flash::warning("warning");
        $args = Flash::readAll();
        self::assertEquals("warning", $args['flash']['warning']);
    }

    public function testSuccess()
    {
        Flash::success("success");
        $args = Flash::readAll();
        self::assertEquals("success", $args['flash']['success']);
    }

    public function testNotice()
    {
        Flash::notice("notice");
        $args = Flash::readAll();
        self::assertEquals("notice", $args['flash']['notice']);
    }

    public function testInfo()
    {
        Flash::info("info");
        $args = Flash::readAll();
        self::assertEquals("info", $args['flash']['info']);
    }
}