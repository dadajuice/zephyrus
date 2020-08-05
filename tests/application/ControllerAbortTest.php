<?php namespace Zephyrus\Tests\application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Network\Router;

class ControllerAbortTest extends TestCase
{
    public function testBadRequest()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

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
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

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
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

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
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

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
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

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
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

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
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::abortNotFound();
            }
        };
        $controller->index()->send();
        self::assertEquals(404, http_response_code());
    }
}