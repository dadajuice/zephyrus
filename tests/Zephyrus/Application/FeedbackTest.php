<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Feedback;
use Zephyrus\Core\Session;

class FeedbackTest extends TestCase
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

    public function testErrors()
    {
        Feedback::register([
            'firstname' => ['Must not be empty'],
            'email' => ['Must not be empty', 'Must be valid'],
            'cart.quantity.2' => ['Must not be empty'],
            'cart.amount.2' => ['Must be a number'],
            'foo.bar.bubu.2.toto' => ['Something wrong']
        ]);
        self::assertEquals(['Must not be empty'], Feedback::read('firstname'));
        self::assertEquals(['Must not be empty'], feedback('firstname'));
        self::assertEquals(['Must not be empty', 'Must be valid'], Feedback::read('email'));
        self::assertEquals(['Must be a number'], Feedback::read('cart[amount][2]'));
        self::assertEquals(['Must not be empty', 'Must be a number'], Feedback::read('cart'));
        self::assertEquals([
            'firstname' => ['Must not be empty'],
            'email' => ['Must not be empty', 'Must be valid'],
            'cart[quantity][2]' => ['Must not be empty'],
            'cart[amount][2]' => ['Must be a number'],
            'foo[bar][bubu][2][toto]' => ['Something wrong']
        ], Feedback::readAll());
        self::assertEquals([
            'firstname', 'email', 'cart[quantity][2]', 'cart[amount][2]', 'foo[bar][bubu][2][toto]'
        ], Feedback::getFieldNames());
        self::assertEquals([
            'firstname', 'email', 'cart[quantity][2]', 'cart[amount][2]', 'foo[bar][bubu][2][toto]'
        ], feedbackFields());
        Feedback::clear();
        self::assertEmpty(Feedback::readAll());
        self::assertEmpty(Feedback::read('firstname'));
    }
}
