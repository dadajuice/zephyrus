<?php namespace Zephyrus\Tests\Application\Controllers;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;

class ControllerAbortTest extends TestCase
{
    public function testBadRequest()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortBadRequest();
            }
        };
        $controller->index()->send();
        self::assertEquals(400, http_response_code());
    }

    public function testInternalError()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortInternalError();
            }
        };
        $controller->index()->send();
        self::assertEquals(500, http_response_code());
    }

    public function testNotAcceptable()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortNotAcceptable();
            }
        };
        $controller->index()->send();
        self::assertEquals(406, http_response_code());
    }

    public function testAbort()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abort(600);
            }
        };
        $controller->index()->send();
        self::assertEquals(600, http_response_code());
    }

    public function testForbidden()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortForbidden();
            }
        };
        $controller->index()->send();
        self::assertEquals(403, http_response_code());
    }

    public function testMethodNotAllowed()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortMethodNotAllowed();
            }
        };
        $controller->index()->send();
        self::assertEquals(405, http_response_code());
    }

    public function testAbortNotFound()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortNotFound();
            }
        };
        $controller->index()->send();
        self::assertEquals(404, http_response_code());
    }

    public function testAbortNotImplemented()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortNotImplemented();
            }
        };
        $controller->index()->send();
        self::assertEquals(501, http_response_code());
    }

    public function testAbortGatewayTimeout()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortGatewayTimeout();
            }
        };
        $controller->index()->send();
        self::assertEquals(504, http_response_code());
    }

    public function testAbortServiceUnavailable()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortServiceUnavailable();
            }
        };
        $controller->index()->send();
        self::assertEquals(503, http_response_code());
    }

    public function testAbortBadGateway()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortBadGateway();
            }
        };
        $controller->index()->send();
        self::assertEquals(502, http_response_code());
    }

    public function testAbortUnprocessableEntity()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortUnprocessableEntity();
            }
        };
        $controller->index()->send();
        self::assertEquals(422, http_response_code());
    }

    public function testAbortConflict()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortConflict();
            }
        };
        $controller->index()->send();
        self::assertEquals(409, http_response_code());
    }

    public function testAbortRequestTimeout()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortRequestTimeout();
            }
        };
        $controller->index()->send();
        self::assertEquals(408, http_response_code());
    }

    public function testAbortUnauthorized()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortUnauthorized();
            }
        };
        $controller->index()->send();
        self::assertEquals(401, http_response_code());
    }

    public function testAbortPaymentRequired()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::abortPaymentRequired();
            }
        };
        $controller->index()->send();
        self::assertEquals(402, http_response_code());
    }
}