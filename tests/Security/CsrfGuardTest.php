<?php namespace Zephyrus\Tests\Security;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zephyrus\Application\Session;
use Zephyrus\Exceptions\InvalidCsrfException;
use Zephyrus\Network\Request;
use Zephyrus\Security\CsrfGuard;

class CsrfGuardTest extends TestCase
{
    public function testHiddenFields()
    {
        $req = new Request('http://test.local/test', 'GET');
        $csrf = new CsrfGuard($req);
        $result = $csrf->generateHiddenFields();
        self::assertTrue($this->hasHiddenFields($result));
        self::assertTrue($csrf->isHtmlIntegrationEnabled());
        self::assertTrue($csrf->isEnabled());
    }

    public function testGuard()
    {
        $req = new Request('http://test.local/test', 'GET');
        $csrf = new CsrfGuard($req, [
            'guard_methods' => ['DELETE']
        ]);
        $csrf->run();
        $output = $csrf->generateHiddenFields();
        $fields = $this->getHiddenFieldValues($output);
        $name = $fields[1];
        $value = $fields[2];

        $req = new Request('http://test.local/test', 'DELETE', ['parameters' => ['CSRFName' => $name, 'CSRFToken' => $value]]);
        $csrf = new CsrfGuard($req, [
            'guard_methods' => ['DELETE']
        ]);
        $csrf->run();
        $test = "success";
        self::assertEquals("success", $test);
    }

    public function testFormInject()
    {
        $req = new Request('http://test.local/test', 'POST');
        $csrf = new CsrfGuard($req);
        $html = '<html><body><form action="test" method="get"><input type="text" name="test" /></form></body>';
        $result = $csrf->injectForms($html);
        self::assertTrue($this->hasHiddenFields($result));
    }

    public function testFormInjectExclusion()
    {
        $req = new Request('http://test.local/test', 'POST');
        $csrf = new CsrfGuard($req);
        $html = '<html><body><form nocsrf="true" action="test" method="get"><input type="text" name="test" /></form></body>';
        $result = $csrf->injectForms($html);
        self::assertEquals($html, $result);
    }

    public function testProperties()
    {
        $req = new Request('http://test.local/test', 'POST');
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
        $req = new Request('http://test.local/test', 'POST');
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
        $req = new Request('http://test.local/test/toto', 'POST');
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
        $req = new Request('http://test.local/test', 'POST');
        new CsrfGuard($req, [
            'enabled' => true,
            'guard_methods' => ['GET', 'POST', 'PUT', 'OUPPPS', 'DELETE']
        ]);
    }

    public function testGuardException()
    {
        $this->expectException(InvalidCsrfException::class);
        $req = new Request('http://test.local/test', 'POST');
        $csrf = new CsrfGuard($req);
        $csrf->run();
    }

    public function testInvalidToken()
    {
        $this->expectException(InvalidCsrfException::class);
        $req = new Request('http://test.local/test', 'PUT', ['parameters' => ['CSRFName' => 'invalid', 'CSRFToken' => 'invalid']]);
        $csrf = new CsrfGuard($req);
        $csrf->run();
    }

    public function testInvalidToken2()
    {
        $this->expectException(InvalidCsrfException::class);
        $req = new Request('http://test.local/test', 'PATCH', ['parameters' => ['CSRFName' => 'invalid', 'CSRFToken' => 'invalid']]);
        $csrf = new CsrfGuard($req);
        $csrf->run();
    }

    public function testInvalidGuard()
    {
        $this->expectException(InvalidCsrfException::class);
        $req = new Request('http://test.local/test', 'POST', ['parameters' => ['CSRFName' => 'invalid', 'CSRFToken' => 'invalid']]);
        $csrf = new CsrfGuard($req);
        $csrf->run();
    }

    public function testInvalidGetGuard()
    {
        $this->expectException(InvalidCsrfException::class);
        $req = new Request('http://test.local/test', 'GET');
        $csrf = new CsrfGuard($req, [
            'guard_methods' => ['GET']
        ]);
        $csrf->run();
    }

    public function testNoStorageGuard()
    {
        $this->expectException(InvalidCsrfException::class);
        $req = new Request('http://test.local/test', 'GET');
        $csrf = new CsrfGuard($req);
        $csrf->run();
        $output = $csrf->generateHiddenFields();
        $fields = $this->getHiddenFieldValues($output);
        $name = $fields[1];
        $value = $fields[2];

        $req = new Request('http://test.local/test', 'DELETE', ['parameters' => ['CSRFName' => $name, 'CSRFToken' => $value]]);
        $csrf = new CsrfGuard($req);
        Session::getInstance()->remove('__CSRF_TOKEN');
        $csrf->run();
    }

    private function hasHiddenFields($html): bool
    {
        return (bool) preg_match("/<input type=\"hidden\" name=\"CSRFName\" value=\"CSRFGuard_[0-9]+\" \/><input type=\"hidden\" name=\"CSRFToken\" value=\"[0-9a-zA-Z]+\" \/>/", $html);
    }

    private function getHiddenFieldValues($html): array
    {
        $output = [];
        preg_match("/<input type=\"hidden\" name=\"CSRFName\" value=\"(CSRFGuard_[0-9]+)\" \/><input type=\"hidden\" name=\"CSRFToken\" value=\"([0-9a-zA-Z]+)\" \/>/", $html, $output);
        return $output;
    }
}