<?php namespace Zephyrus\Tests\Application;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use UnderflowException;
use Zephyrus\Application\ErrorHandler;
use Zephyrus\Database\Core\Database;
use Zephyrus\Exceptions\DatabaseException;

class ErrorHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        set_exception_handler(null);
        set_error_handler(null);
    }

    public function testInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        $handler = ErrorHandler::getInstance();
        $handler->exception(function() {});
    }

    public function testInvalidArgumentType()
    {
        $this->expectException(InvalidArgumentException::class);
        $handler = ErrorHandler::getInstance();
        $handler->exception(function(Database $e) {});
    }

    public function testInvalidErrorTooMuchArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        $handler = ErrorHandler::getInstance();
        $handler->notice(function ($message, $a, $b, $c, $d) {
            echo "test: $message" ;
        });
    }

    public function testNotice()
    {
        $handler = ErrorHandler::getInstance();
        $handler->notice(function ($message) {
            echo "test: $message" ;
        });

        ob_start();
        trigger_error("an error!", E_USER_NOTICE);
        $output = ob_get_clean();
        self::assertEquals("test: an error!", $output);
    }

    public function testWarning()
    {
        $handler = ErrorHandler::getInstance();
        $handler->warning(function ($message) {
            echo "test: $message" ;
        });

        ob_start();
        trigger_error("a warning!", E_USER_WARNING);
        $output = ob_get_clean();
        self::assertEquals("test: a warning!", $output);

        $handler->restoreDefaultErrorHandler();
        $handler->restoreDefaultExceptionHandler();
        $handler->restoreDefaultHandlers();

        set_error_handler(function () {
            echo "yes";
        });

        ob_start();
        trigger_error("a warning!", E_USER_WARNING);
        $output = ob_get_clean();
        self::assertEquals("yes", $output);
    }

    public function testError()
    {
        $handler = ErrorHandler::getInstance();
        $handler->error(function ($message) {
            echo "test: $message" ;
        });

        ob_start();
        trigger_error("an error!", E_USER_ERROR);
        $output = ob_get_clean();
        self::assertEquals("test: an error!", $output);
    }

    public function testExceptionSpecific()
    {
        $handler = ErrorHandler::getInstance();
        $handler->exception(function (DatabaseException $exception) {
            echo "test: " . $exception->getMessage() ;
        });
        $lastHandler = set_exception_handler(null);
        ob_start();

        // Manually trigger the inner exceptionHandler method to mimic exception thrown. Needed because of phpunit
        // inner exception handler.
        $lastHandler[0]->exceptionHandler(new DatabaseException("oui"));
        $output = ob_get_clean();
        self::assertEquals("test: oui", $output);
    }

    public function testExceptionParent()
    {
        $handler = ErrorHandler::getInstance();
        $handler->exception(function (LogicException $exception) {
            echo "test: " . $exception->getMessage() ;
        });
        $lastHandler = set_exception_handler(null);
        ob_start();

        // Manually trigger the inner exceptionHandler method to mimic exception thrown. Needed because of phpunit
        // inner exception handler.
        $lastHandler[0]->exceptionHandler(new \InvalidArgumentException("oui"));
        $output = ob_get_clean();
        self::assertEquals("test: oui", $output);
    }

    public function testExceptionParentOfParent()
    {
        $handler = ErrorHandler::getInstance();
        $handler->exception(function (UnderflowException $exception) {
            echo "test: " . $exception->getMessage() ;
        });
        $lastHandler = set_exception_handler(null);
        ob_start();

        // Manually trigger the inner exceptionHandler method to mimic exception thrown. Needed because of phpunit
        // inner exception handler.
        $lastHandler[0]->exceptionHandler(new \BadMethodCallException("oui"));
        $output = ob_get_clean();
        self::assertEquals("test: oui", $output);
    }

    public function testExceptionNotFound()
    {
        $handler = ErrorHandler::getInstance();
        $handler->exception(function (\RuntimeException $exception) {
            echo "test: " . $exception->getMessage() ;
        });
        $lastHandler = set_exception_handler(null);
        ob_start();
        $lastHandler[0]->exceptionHandler(new \Exception("oui"));
        $output = ob_get_clean();

        $found = str_contains($output, '( ! )</span> oui in');
        self::assertTrue($found);
    }
}