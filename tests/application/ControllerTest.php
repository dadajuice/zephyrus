<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Application\Feedback;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Session;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Network\Response;
use Zephyrus\Network\ResponseFactory;
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
                return $this->html('test');
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test', $output);
    }

    public function testBeforeMiddleware()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/', 'index');
            }

            public function before()
            {
                return ResponseFactory::getInstance()->buildJson(['test' => 'success']);
            }

            public function index()
            {
                return $this->html('test html');
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('{"test":"success"}', $output);
    }

    public function testAfterMiddleware()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/', 'index');
            }

            public function after(?Response $response)
            {
                return ResponseFactory::getInstance()->buildJson(['test' => 'success']);
            }

            public function index()
            {
                return $this->html('test html');
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('{"test":"success"}', $output);
    }

    public function testRender()
    {
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);

        $router = new Router();
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
        self::assertEquals('<p>Bob Lewis</p>', $output);
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

    public function testRenderPhpWithFlashAndFeedback()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index(): Response
            {
                Flash::error("invalid");
                Feedback::error(["email" => ["test"]]);
                return parent::render('test3');
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertEquals('<h1>invalid</h1><h1>test</h1>', $output);
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
        $req = new Request('http://test.local/delete', 'delete', ['parameters' => ['t' => '4']]);
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
        $req = new Request('http://test.local/delete', 'delete', [
            'parameters' => ['t' => '4']
        ]);
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

    public function testPlain()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::plain("test plain");
            }
        };
        ob_start();
        $controller->index()->send();
        $headers = xdebug_get_headers();
        $output = ob_get_clean();
        self::assertEquals('test plain', $output);
        self::assertTrue(in_array('Content-Type: text/plain;charset=UTF-8', $headers));
    }

    public function testDownload()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::download(ROOT_DIR . '/config.ini');
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        $headers = xdebug_get_headers();
        self::assertTrue(strpos($output, "[application]") !== false);
        self::assertTrue(in_array('Content-Disposition:attachment; filename="config.ini"', $headers));
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
