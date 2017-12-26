<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Application\Session;
use Zephyrus\Application\SessionStorage;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;

class ControllerTest extends TestCase
{
    public function testGetRouting()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/', 'index');
            }

            public function index()
            {
                echo 'test';
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test', $output);
    }

    public function testRender()
    {
        $router = new Router();
        $storage = new SessionStorage('bob');
        Session::getInstance()->setSessionStorage($storage);
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index(): Response
            {
                return parent::render('test', ['item' => ['name' => 'Bob Lewis', 'price' => 12.30]]);
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertEquals('<p>Example Bob Lewis</p>', $output);
    }

    public function testRenderPhp()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index(): Response
            {
                return parent::render('test2', ['a' => 'allo']);
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertEquals('<h1>allo</h1>', $output);
    }

    public function testGetRoutingWithParameter()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/bob/{id}', 'index');
            }

            public function index($id)
            {
                echo 'test' . $id;
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/bob/4', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test4', $output);
    }

    public function testPostRouting()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::post('/insert', 'insert');
            }

            public function insert()
            {
                echo 'test';
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/insert', 'post');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test', $output);
    }

    public function testPutRouting()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::put('/update', 'update');
            }

            public function update()
            {
                echo 'test';
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/update', 'put');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test', $output);
    }

    public function testDeleteRouting()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::delete('/delete', 'remove');
            }

            public function remove()
            {
                $t = $this->request->getParameter('t');
                echo 'test' . $t;
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/delete', 'delete', ['t' => '4']);
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test4', $output);
    }

    public function testBuildForm()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::delete('/delete', 'remove');
            }

            public function remove()
            {
                $form = parent::buildForm();
                $t = $form->getValue('t');
                echo 'test' . $t;
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/delete', 'delete', ['t' => '4']);
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test4', $output);
    }

    public function testJson()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::json(['test' => ['a' => 2, 'b' => 'bob']]);
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertEquals('{"test":{"a":2,"b":"bob"}}', $output);
    }

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

    /**
     * @expectedException \RuntimeException
     */
    public function testXmlException()
    {
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

    public function testHtml()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::html("<html>test</html>");
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertEquals('<html>test</html>', $output);
    }

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

    // Needs to find a way to execute and cut response after one loop tick
    /*public function testStreamingSse()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::sseStreaming(function () {
                    return "works";
                }, "test");
            }
        };
        ob_start();
        $controller->index()->send();

        $output = ob_get_clean();
        var_dump($output);
        //self::assertTrue(strpos($output, 'data: "test"') !== false);
    }*/

    public function testRedirect()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::redirect('/test');
            }
        };
        $controller->index()->send();
        $headers = xdebug_get_headers();
        self::assertTrue(in_array('Location:/test', $headers));
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
}
