<?php namespace Zephyrus\Tests\Security;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zephyrus\Core\Session;
use Zephyrus\Exceptions\Security\InvalidCsrfException;
use Zephyrus\Exceptions\Security\MissingCsrfException;
use Zephyrus\Network\HttpMethod;
use Zephyrus\Security\CsrfGuard;
use Zephyrus\Tests\RequestUtility;

class CsrfGuardTest extends TestCase
{
    public function testHiddenFields()
    {
        $req = RequestUtility::get("/test");
        $csrf = new CsrfGuard($req);
        $result = $csrf->generateHiddenFields();
        self::assertTrue($this->hasHiddenFields($result));
        self::assertTrue($csrf->isHtmlIntegrationEnabled());
        self::assertTrue($csrf->isEnabled());
    }

    public function testGuard()
    {
        $req = RequestUtility::get("/test");
        $csrf = new CsrfGuard($req, [
            'guard_methods' => ['DELETE']
        ]);
        $csrf->run();
        $output = $csrf->generateHiddenFields();
        $fields = $this->getHiddenFieldValues($output);
        $name = $fields[1];
        $value = $fields[2];

        $req = RequestUtility::delete("/test", 'CSRFToken=' . $name . '$' . $value);
        $csrf = new CsrfGuard($req, [
            'guard_methods' => ['DELETE']
        ]);
        $csrf->run();
        self::assertEquals($name . '$' . $value, $req->getParameter('CSRFToken'));
    }

    public function testFormInject()
    {
        $req = RequestUtility::post("/test");
        $csrf = new CsrfGuard($req);
        $html = '<html><body><form action="test" method="get"><input type="text" name="test" /></form></body>';
        $result = $csrf->injectForms($html);
        self::assertTrue($this->hasHiddenFields($result));
    }

    public function testFormInjectExclusion()
    {
        $req = RequestUtility::post("/test");
        $csrf = new CsrfGuard($req);
        $html = '<html><body><form nocsrf="true" action="test" method="get"><input type="text" name="test" /></form></body>';
        $result = $csrf->injectForms($html);
        self::assertEquals($html, $result);
    }

    public function testProperties()
    {
        $req = RequestUtility::post("/test");
        $csrf = new CsrfGuard($req, [
            'enabled' => false,
            'guard_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
            'html_integration_enabled' => false
        ]);
        self::assertFalse($csrf->isEnabled());
        self::assertFalse($csrf->isHtmlIntegrationEnabled());
        self::assertTrue($csrf->isDeleteSecured());
        self::assertTrue($csrf->isGetSecured());
        self::assertTrue($csrf->isPostSecured());
        self::assertTrue($csrf->isPutSecured());
        self::assertTrue($csrf->isPatchSecured());
    }

    public function testRequestException()
    {
        $req = RequestUtility::post("/test");
        $csrf = new CsrfGuard($req, [
            'enabled' => true,
            'guard_methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
            'html_integration_enabled' => true,
            'exceptions' => ['/test'] // Direct exception
        ]);
        self::assertTrue($csrf->isPostSecured());
        $csrf->run();
        self::assertTrue(true); // if reach is ok
    }

    public function testRequestRegexException()
    {
        $req = RequestUtility::post("/test/toto");
        $csrf = new CsrfGuard($req, [
            'enabled' => true,
            'guard_methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
            'html_integration_enabled' => true,
            'exceptions' => ['\/test.*'] // Regex to validate all route that begins with /test
        ]);
        self::assertTrue($csrf->isPostSecured());
        $csrf->run();
        self::assertTrue(true); // if reach is ok
    }

    public function testInvalidGuardMethods()
    {
        $this->expectException(RuntimeException::class);
        $req = RequestUtility::post("/test");
        new CsrfGuard($req, [
            'enabled' => true,
            'guard_methods' => ['GET', 'POST', 'PUT', 'OUPPPS', 'DELETE']
        ]);
    }

    public function testGuardMissingException()
    {
        $this->expectException(MissingCsrfException::class);
        $this->expectExceptionMessage("ZEPHYRUS SECURITY: The submitted form is missing the needed CSRF tokens. The requested route [POST /test] is configured to proceed the CSRF mitigation. If you think this is not the case, you can add the route to the CSRF exceptions, use the 'nocsrf' attribute on the &lt;form&gt; or disable the feature.");
        $req = RequestUtility::buildFormRequest("/test", HttpMethod::POST);
        $csrf = new CsrfGuard($req);
        $csrf->run();
    }

    public function testInvalidToken()
    {
        $this->expectException(InvalidCsrfException::class);
        $req = RequestUtility::put("/test", "CSRFToken=invalid");
        $csrf = new CsrfGuard($req);
        $csrf->run();
    }

    public function testGuardInvalidException()
    {
        $req = RequestUtility::put("/test", "CSRFToken=invalid");
        $csrf = new CsrfGuard($req);
        try {
            $csrf->run();
        } catch (InvalidCsrfException $e) {
            self::assertEquals(HttpMethod::PUT, $e->getMethod());
            self::assertEquals("ZEPHYRUS SECURITY: The provided CSRF token for the requested route [PUT /test] is invalid or has expired.", $e->getMessage());
        }
    }

    public function testInvalidToken2()
    {
        $this->expectException(InvalidCsrfException::class);
        $req = RequestUtility::patch("/test", "CSRFToken=invalid");
        $csrf = new CsrfGuard($req);
        $csrf->run();
    }

    public function testInvalidGuard()
    {
        $this->expectException(InvalidCsrfException::class);
        $req = RequestUtility::post("/test", "CSRFToken=invalid");
        $csrf = new CsrfGuard($req);
        $csrf->run();
    }

    public function testInvalidGetGuard()
    {
        $this->expectException(MissingCsrfException::class);
        $req = RequestUtility::get("/test");
        $csrf = new CsrfGuard($req, [
            'guard_methods' => ['GET']
        ]);
        $csrf->run();
    }

    public function testNoStorageGuard()
    {
        $this->expectException(InvalidCsrfException::class);
        $req = RequestUtility::get("/test");
        $csrf = new CsrfGuard($req);
        $csrf->run();
        $output = $csrf->generateHiddenFields();
        $fields = $this->getHiddenFieldValues($output);
        $name = $fields[1];
        $value = $fields[2];

        $req = RequestUtility::delete("/test", "CSRFToken=" . $name . '$' . $value);
        $csrf = new CsrfGuard($req);
        Session::remove('__CSRF_TOKEN');
        $csrf->run();
    }

    private function hasHiddenFields($html): bool
    {
        return (bool) preg_match("/<input type=\"hidden\" name=\"CSRFToken\" value=\"CSRFGuard_[0-9]+\\$[0-9a-zA-Z]+\" \/>/", $html);
    }

    private function getHiddenFieldValues($html): array
    {
        $output = [];
        preg_match("/<input type=\"hidden\" name=\"CSRFToken\" value=\"(CSRFGuard_[0-9]+)\\$([0-9a-zA-Z]+)\" \/>/", $html, $output);
        return $output;
    }
}