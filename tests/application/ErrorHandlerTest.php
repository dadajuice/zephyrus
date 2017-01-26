<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\ErrorHandler;
use Zephyrus\Database\Database;

class ErrorHandlerTest extends TestCase
{
    protected function tearDown()
    {
        set_exception_handler(null);
        set_error_handler(null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArgument()
    {
        $handler = new ErrorHandler();
        $handler->exception(function() {});
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArgumentType()
    {
        $handler = new ErrorHandler();
        $handler->exception(function(Database $e) {});
    }
}