<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Callback;

class CallbackTest extends TestCase
{
    public function testNoArgumentFunction()
    {
        $callback = new Callback(function() {
            return 'test';
        });
        $result = $callback->execute();
        self::assertEquals('test', $result);
    }

    public function testArgumentFunction()
    {
        $callback = new Callback(function($i) {
            return $i * 2;
        });
        $result = $callback->execute(3);
        self::assertEquals(6, $result);
    }

    public function testMultipleArgumentsFunction()
    {
        $callback = new Callback(function($i, $j) {
            return $i + $j;
        });
        $result = $callback->execute(3, 2);
        self::assertEquals(5, $result);
    }

    public function testClassMethod()
    {
        $callback = new Callback(['\Zephyrus\Tests\ValidationClass', 'validPrice']);
        $result = $callback->execute(3);
        self::assertFalse($result);
    }

    public function testObjectMethod()
    {
        $obj = new ValidationClass();
        $callback = new Callback([$obj, 'validPrice']);
        $result = $callback->execute(3);
        self::assertFalse($result);
    }

    public function testStaticMethod()
    {
        $callback = new Callback(['\Zephyrus\Utilities\Validation', 'isAlphanumeric']);
        $result = $callback->execute('ewretyr-345');
        self::assertFalse($result);
    }
}

class ValidationClass
{
    public function validPrice($value)
    {
        return $value > 10;
    }
}