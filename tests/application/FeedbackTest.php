<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Feedback;
use Zephyrus\Application\Session;

class FeedbackTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $session = Session::getInstance();
        $session->start();
    }

    public static function tearDownAfterClass(): void
    {
        $session = Session::getInstance();
        $session->destroy();
        Session::kill();
    }

    public function testError()
    {
        Feedback::error(["email" => ["invalid"]]);
        $args = Feedback::readAll();
        self::assertEquals("invalid", $args['feedback']['error']['email'][0]);
    }
}