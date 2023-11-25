<?php namespace Zephyrus\Network\Router;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Core\Session;
use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Network\HttpMethod;
use Zephyrus\Network\Request;
use Zephyrus\Network\Router;
use Zephyrus\Security\AuthorizationRepository;
use Zephyrus\Security\CsrfGuard;
use Zephyrus\Tests\Simulation\AttributeExampleController;
use Zephyrus\Tests\RequestUtility;

class RouteAttributeTest extends TestCase
{
    public function testRouteNotFound()
    {
        $controller = new class() extends Controller {

            #[Get("/test")]
            public function test()
            {
                return $this->plain('test');
            }
        };

        $repository = new RouteRepository();
        $controller::initializeRoutes($repository);
        $req = RequestUtility::get("/toto");
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("The specified route [/toto] has not been defined for method [GET]");
        (new Router($repository))->resolve($req);
    }

    public function testRouteMethodNotFound()
    {
        $controller = new class() extends Controller {

            #[Get("/test")]
            public function test()
            {
                return $this->plain('test');
            }
        };

        $repository = new RouteRepository();
        $controller::initializeRoutes($repository);
        $req = RequestUtility::post("/test");
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage("The specified route [/test] has not been defined for method [POST]");
        (new Router($repository))->resolve($req);
    }

    public function testGetRouting()
    {
        $controller = new class() extends Controller {

            #[Get("/")]
            public function index()
            {
                return $this->plain('index');
            }

            #[Get("/test")]
            public function test()
            {
                return $this->plain('test');
            }

            #[Post("/login")]
            public function login()
            {
                return $this->plain('this is sparta');
            }

            #[Put("/test")]
            public function update()
            {
                return $this->plain('this is update');
            }

            #[Patch("/test")]
            public function partialUpdate()
            {
                return $this->plain('this is partial update');
            }

            #[Delete("/test")]
            public function remove()
            {
                return $this->plain('this is delete');
            }
        };
        $repository = new RouteRepository();
        $controller::initializeRoutes($repository);

        $this->assertCount(2, $repository->getRoutes(HttpMethod::GET));

        $req = RequestUtility::get("/test");
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('test', $response->getContent());

        $req = RequestUtility::get("/");
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('index', $response->getContent());

        $req = RequestUtility::post('/login');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is sparta', $response->getContent());

        $req = RequestUtility::put('/test');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is update', $response->getContent());

        $req = RequestUtility::patch('/test');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is partial update', $response->getContent());

        $req = RequestUtility::delete('/test');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is delete', $response->getContent());
    }

    public function testRootGetRouting()
    {
        $repository = new RouteRepository();
        AttributeExampleController::initializeRoutes($repository);

        AuthorizationRepository::getInstance()->addRule('everyone', function (Request $request) {
            return true; // Everyone allowed
        });
        AuthorizationRepository::getInstance()->addSessionRule('admin', 'is_admin', true);

        $req = RequestUtility::get("/toto/test");
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('test', $response->getContent());

        $req = RequestUtility::post("/toto/login");
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is sparta', $response->getContent());

        $req = RequestUtility::put("/toto/test");
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is update', $response->getContent());

        Session::set('is_admin', true);
        $req = RequestUtility::patch("/toto/test");
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is partial update', $response->getContent());

        $req = RequestUtility::delete("/toto/test");
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is delete', $response->getContent());
    }
}
