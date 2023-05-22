<?php namespace Zephyrus\Tests\Application\Form;

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

    public function testErrors()
    {
        Feedback::register([
            'firstname' => ['Must not be empty'],
            'email' => ['Must not be empty', 'Must be valid'],
            'cart[].quantity.2' => ['Must not be empty'],
            'cart[].amount.2' => ['Must be a number']
        ]);
        self::assertEquals(['Must not be empty'], Feedback::read('firstname'));
        self::assertEquals(['Must not be empty'], feedback('firstname'));
        self::assertEquals(['Must not be empty', 'Must be valid'], Feedback::read('email'));
        self::assertEquals(['Must be a number'], Feedback::read('cart[].amount.2'));
        self::assertEquals(['Must not be empty', 'Must be a number'], Feedback::read('cart[]'));
        self::assertEquals([
            'firstname' => ['Must not be empty'],
            'email' => ['Must not be empty', 'Must be valid'],
            'cart[].quantity.2' => ['Must not be empty'],
            'cart[].amount.2' => ['Must be a number']
        ], Feedback::readAll());
        Feedback::clear();
        self::assertEmpty(Feedback::readAll());
        self::assertEmpty(Feedback::read('firstname'));
    }
}
