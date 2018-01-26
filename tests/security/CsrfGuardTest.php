<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Session;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Security\CsrfGuard;

class CsrfGuardTest extends TestCase
{
    public function testHiddenFields()
    {
        $csrf = new CsrfGuard();
        $result = $csrf->generateHiddenFields();
        self::assertTrue($this->hasHiddenFields($result));
    }

    public function testGuard()
    {
        $req = new Request('http://test.local/test', 'GET');
        RequestFactory::set($req);
        $csrf = new CsrfGuard();
        $csrf->setDeleteSecured(true);
        $csrf->setGetSecured(false);
        $csrf->setPostSecured(false);
        $csrf->setPutSecured(false);
        $csrf->guard();
        $output = $csrf->generateHiddenFields();
        $fields = $this->getHiddenFieldValues($output);
        $name = $fields[1];
        $value = $fields[2];

        $req = new Request('http://test.local/test', 'DELETE', ['parameters' => ['CSRFName' => $name, 'CSRFToken' => $value]]);
        RequestFactory::set($req);
        $csrf = new CsrfGuard();
        $csrf->setDeleteSecured(true);
        $csrf->setGetSecured(false);
        $csrf->setPostSecured(false);
        $csrf->setPutSecured(false);
        $csrf->guard();
        $test = "success";
        self::assertEquals("success", $test);
    }

    public function testFormInject()
    {
        $csrf = new CsrfGuard();
        $html = '<html><body><form action="test" method="get"><input type="text" name="test" /></form></body>';
        $result = $csrf->injectForms($html);
        self::assertTrue($this->hasHiddenFields($result));
    }

    public function testFormInjectExclusion()
    {
        $csrf = new CsrfGuard();
        $html = '<html><body><form nocsrf="true" action="test" method="get"><input type="text" name="test" /></form></body>';
        $result = $csrf->injectForms($html);
        self::assertEquals($html, $result);
    }

    public function testProperties()
    {
        $csrf = new CsrfGuard();
        $csrf->setDeleteSecured(true);
        $csrf->setPostSecured(true);
        $csrf->setPutSecured(true);
        $csrf->setGetSecured(true);
        self::assertTrue($csrf->isDeleteSecured());
        self::assertTrue($csrf->isGetSecured());
        self::assertTrue($csrf->isPostSecured());
        self::assertTrue($csrf->isPutSecured());
    }

    /**
     * @expectedException \Zephyrus\Exceptions\InvalidCsrfException
     */
    public function testGuardException()
    {
        $req = new Request('http://test.local/test', 'POST');
        RequestFactory::set($req);
        $csrf = new CsrfGuard();
        $csrf->guard();
    }

    /**
     * @expectedException \Zephyrus\Exceptions\InvalidCsrfException
     */
    public function testInvalidToken()
    {
        $req = new Request('http://test.local/test', 'PUT', ['parameters' => ['CSRFName' => 'invalid', 'CSRFToken' => 'invalid']]);
        RequestFactory::set($req);
        $csrf = new CsrfGuard();
        $csrf->guard();
    }

    /**
     * @expectedException \Zephyrus\Exceptions\InvalidCsrfException
     */
    public function testInvalidGuard()
    {
        $req = new Request('http://test.local/test', 'POST', ['parameters' => ['CSRFName' => 'invalid', 'CSRFToken' => 'invalid']]);
        RequestFactory::set($req);
        $csrf = new CsrfGuard();
        $csrf->guard();
    }

    /**
     * @expectedException \Zephyrus\Exceptions\InvalidCsrfException
     */
    public function testInvalidGetGuard()
    {
        $req = new Request('http://test.local/test', 'GET');
        RequestFactory::set($req);
        $csrf = new CsrfGuard();
        $csrf->setGetSecured(true);
        $csrf->guard();
    }

    /**
     * @expectedException \Zephyrus\Exceptions\InvalidCsrfException
     */
    public function testNoStorageGuard()
    {
        $req = new Request('http://test.local/test', 'GET');
        RequestFactory::set($req);
        $csrf = new CsrfGuard();
        $csrf->setGetSecured(false);
        $csrf->guard();
        $output = $csrf->generateHiddenFields();
        $fields = $this->getHiddenFieldValues($output);
        $name = $fields[1];
        $value = $fields[2];

        $req = new Request('http://test.local/test', 'DELETE', ['parameters' => ['CSRFName' => $name, 'CSRFToken' => $value]]);
        RequestFactory::set($req);
        $csrf = new CsrfGuard();
        $csrf->setDeleteSecured(true);
        Session::getInstance()->remove('__CSRF_TOKEN');
        $csrf->guard();
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