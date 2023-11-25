<?php namespace Zephyrus\Tests\Application\Controllers;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;

class ControllerStreamTest extends TestCase
{
    public function testPollingSse()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::ssePolling("test");
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertTrue(str_contains($output, 'data: "test"'));
    }

    public function testStreamingSse()
    {
        $controller = new class() extends Controller {

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
        self::assertTrue(str_contains($output, 'data: "works"'));
    }

    public function testFlowSse()
    {
        $controller = new class() extends Controller {

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
        self::assertTrue(str_contains($output, 'data: "toto"'));
    }
}
