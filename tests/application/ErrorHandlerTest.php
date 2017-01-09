<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\ErrorHandler;
use Zephyrus\Database\Database;
use Zephyrus\Exceptions\DatabaseException;
use Zephyrus\Exceptions\RouteDefinitionException;

class ErrorHandlerTest extends TestCase
{
    protected function setUp()
    {
        set_exception_handler(null);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArgument()
    {
        ErrorHandler::getInstance()->exception(function() {});
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArgumentType()
    {
        ErrorHandler::getInstance()->exception(function(Database $e) {});
    }

    /**
     * @expectedException \Zephyrus\Exceptions\DatabaseException
     */
    public function testDatabaseException()
    {
        ErrorHandler::getInstance()->exception(function(DatabaseException $e) {
            self::assertEquals('SELECT * FROM TEST', $e->getQuery());
        });
        throw new DatabaseException("DB error");
    }
}