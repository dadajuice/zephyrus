<?php namespace Zephyrus\Tests\application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Network\Router;

class ControllerXmlTest extends TestCase
{
    public function testXmlArray()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::xml(['test' => ['a' => '2', 'b' => 'bob', 3 => 't']], 'root');
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertTrue(strpos($output, '<root><test><a>2</a><b>bob</b><node3>t</node3></test></root>') !== false);
    }

    public function testXmlException()
    {
        $this->expectException(\RuntimeException::class);
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::xml("sfdg");
            }
        };
        $controller->index();
    }

    public function testXmlObject()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                $xmlstr = "<?xml version='1.0' ?><movies><movie><title>The Dark Knight</title><year>2008</year></movie></movies>";
                $movies = new \SimpleXMLElement($xmlstr);
                return parent::xml($movies);
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertTrue(strpos($output, '<movies><movie><title>The Dark Knight</title><year>2008</year></movie></movies>') !== false);
    }
}
