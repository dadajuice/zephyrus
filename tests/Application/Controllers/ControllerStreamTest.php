<?php namespace Zephyrus\Tests\Application\Controllers;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Application\Session;
use Zephyrus\Network\Router;

class ControllerStreamTest extends TestCase
{
    public function testPollingSse()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::ssePolling("test");
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertTrue(strpos($output, 'data: "test"') !== false);
    }

    public function testStreamingSse()
    {
        Session::kill();
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                $i = 0;
                return parent::sseStreaming(function () use(&$i) {
                    if ($i >= 1000) { // to test memory leak mitigation
                        return false;
                    }
                    ++$i;
                    return "works";
                }, 'test', 0.1);
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertTrue(strpos($output, 'data: "works"') !== false);
    }

    public function testFlowSse()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::sseFlow(function ($send) {
                    $send(1, "test");
                    $send(2, "toto");
                });
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertTrue(strpos($output, 'data: "toto"') !== false);
    }
}
