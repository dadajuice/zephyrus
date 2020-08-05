<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Router;
use Zephyrus\Exceptions\IntrusionDetectionException;
use Zephyrus\Exceptions\InvalidCsrfException;
use Zephyrus\Exceptions\UnauthorizedAccessException;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Security\Authorization;
use Zephyrus\Security\ContentSecurityPolicy;
use Zephyrus\Security\Controller;
use Zephyrus\Security\IntrusionDetection;

class ASecurityControllerTest extends TestCase
{
    public function testGetRouting()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/', 'index');
                parent::get('/users', 'test');
            }

            public function before(): ?Response
            {
                $this->applyContentSecurityPolicies();
                try {
                    parent::before();
                } catch (IntrusionDetectionException $exception) {
                    $data = $exception->getIntrusionData();
                    if ($data['impact'] >= 10) {
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

        $ids = IntrusionDetection::getInstance();
        $ids->setSurveillance(IntrusionDetection::GET | IntrusionDetection::POST | IntrusionDetection::REQUEST
            | IntrusionDetection::COOKIE);

        $controller->initializeRoutes();
        $req = new Request('http://test.local/', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();

        $guard = $controller->getCsrfGuard();
        self::assertTrue($guard->isPostSecured());

        // CSRF tokens applied
        self::assertTrue(strpos($output, '<input type="hidden" name="CSRFName"') !== false);
        self::assertTrue(strpos($output, '<input type="hidden" name="CSRFToken"') !== false);

        // Headers
        self::assertTrue(strpos(xdebug_get_headers()[49], "Content-Security-Policy") !== false);
    }

    public function testGetRoutingFailed()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/', 'index');
                parent::get('/users', 'test');
            }

            public function before(): ?Response
            {
                $this->setupAuthorizations();
                try {
                    parent::before();
                } catch (IntrusionDetectionException $exception) {
                    $data = $exception->getIntrusionData();
                    if ($data['impact'] >= 10) {
                        return $this->abortForbidden();
                    }
                } catch (InvalidCsrfException $exception) {
                    return $this->abortForbidden();
                } catch (UnauthorizedAccessException $exception) {
                    return $this->html("invalid access");
                }

                return null;
            }

            private function setupAuthorizations()
            {
                parent::getAuthorization()->setMode(Authorization::MODE_BLACKLIST);
                parent::getAuthorization()->addSessionRule('sudo', 'AUTH_LEVEL', 'sudo');
                parent::getAuthorization()->addRule('public', function () {
                    return true;
                });
                parent::getAuthorization()->protect('/users', Authorization::ALL, 'sudo');
                parent::getAuthorization()->protect('/', Authorization::ALL, 'public');
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

        $controller->initializeRoutes();
        $req = new Request('http://test.local/users', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals("invalid access", $output);
    }
}
