<?php namespace Zephyrus\Tests\Security;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Router;
use Zephyrus\Exceptions\IntrusionDetectionException;
use Zephyrus\Exceptions\InvalidCsrfException;
use Zephyrus\Exceptions\UnauthorizedAccessException;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\RouteRepository;
use Zephyrus\Security\Authorization;
use Zephyrus\Security\ContentSecurityPolicy;
use Zephyrus\Security\Controller;

class SecurityControllerTest extends TestCase
{
    public function testGetRouting()
    {
        $repository = new RouteRepository();
        $router = new Router($repository);
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
            {
                parent::get('/', 'index');
                parent::get('/users', 'test');
            }

            public function setupSecurity(): void
            {
                $this->applyContentSecurityPolicies();
            }

            public function before(): ?Response
            {
                try {
                    parent::before();
                } catch (IntrusionDetectionException $exception) {
                    if ($exception->getImpact() >= 10) {
                        return $this->abortForbidden();
                    }
                } catch (InvalidCsrfException $exception) {
                    return $this->abortForbidden();
                } catch (UnauthorizedAccessException $exception) {
                    return $this->html("invalid access");
                }

                return null;
            }

            private function applyContentSecurityPolicies()
            {
                $csp = new ContentSecurityPolicy();
                $csp->setDefaultSources(["'self'"]);
                $csp->setFontSources(["'self'", 'https://fonts.googleapis.com', 'https://fonts.gstatic.com']);
                $csp->setStyleSources(["'self'", 'https://fonts.googleapis.com']);
                $csp->setScriptSources(["'self'", 'https://ajax.googleapis.com', 'https://maps.googleapis.com',
                    'https://www.google-analytics.com', 'http://connect.facebook.net']);
                $csp->setChildSources(["'self'", 'http://staticxx.facebook.com']);
                $csp->setImageSources(["'self'", 'data:']);
                $csp->setBaseUri([$this->request->getBaseUrl()]);
                parent::getSecureHeader()->setContentSecurityPolicy($csp);
            }

            public function index()
            {
                return $this->html('test<form><input type="text" /></form>');
            }

            public function test()
            {
                return $this->html('secured access');
            }
        };

        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/', 'get');
        $response = $router->resolve($req);

        // CSRF tokens applied
        self::assertTrue(str_contains($response->getContent(), '<input type="hidden" name="CSRFToken"'));
    }

    public function testGetRoutingFailed()
    {
        $repository = new RouteRepository();
        $router = new Router($repository);
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
            {
                parent::get('/', 'index');
                parent::get('/users', 'test');
            }

            public function setupSecurity(): void
            {
                parent::getAuthorization()->setMode(Authorization::MODE_BLACKLIST);
                parent::getAuthorization()->addSessionRule('sudo', 'AUTH_LEVEL', 'sudo');
                parent::getAuthorization()->addRule('public', function () {
                    return true;
                });
                parent::getAuthorization()->protect('/users', Authorization::ALL, 'sudo');
                parent::getAuthorization()->protect('/', Authorization::ALL, 'public');
            }

            public function before(): ?Response
            {
                try {
                    parent::before();
                } catch (IntrusionDetectionException $exception) {
                    if ($exception->getImpact() >= 10) {
                        return $this->abortForbidden();
                    }
                } catch (InvalidCsrfException $exception) {
                    return $this->abortForbidden();
                } catch (UnauthorizedAccessException $exception) {
                    return $this->html("invalid access");
                }

                return null;
            }

            public function index()
            {
                return $this->html('test<form><input type="text" /></form>');
            }

            public function test()
            {
                return $this->html('secured access');
            }
        };

        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users', 'get');
        $response = $router->resolve($req);
        self::assertEquals("invalid access", $response->getContent());
    }
}
