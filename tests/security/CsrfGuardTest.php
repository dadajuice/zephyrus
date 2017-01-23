<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Security\CsrfGuard;

class CsrfGuardTest extends TestCase
{
    public function testHiddenFields()
    {
        $csrf = CsrfGuard::getInstance();
        $result = $csrf->generateHiddenFields();
        self::assertTrue($this->hasHiddenFields($result));
    }

    public function testFormInject()
    {
        $csrf = CsrfGuard::getInstance();
        $html = '<html><body><form action="test" method="get"><input type="text" name="test" /></form></body>';
        $result = $csrf->injectForms($html);
        self::assertTrue($this->hasHiddenFields($result));
    }

    public function testFormInjectExclusion()
    {
        $csrf = CsrfGuard::getInstance();
        $html = '<html><body><form nocsrf="true" action="test" method="get"><input type="text" name="test" /></form></body>';
        $result = $csrf->injectForms($html);
        self::assertEquals($html, $result);
    }

    public function testProperties()
    {
        $csrf = CsrfGuard::getInstance();
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
    public function testGuard()
    {
        CsrfGuard::kill();
        $req = new Request('http://test.local/test', 'POST');
        RequestFactory::set($req);
        $csrf = CsrfGuard::getInstance();
        $csrf->guard();
    }

    /**
     * @expectedException \Zephyrus\Exceptions\InvalidCsrfException
     */
    public function testInvalidGuard()
    {
        CsrfGuard::kill();
        $req = new Request('http://test.local/test', 'POST', ['CSRFName' => 'invalid', 'CSRFToken' => 'invalid']);
        RequestFactory::set($req);
        $csrf = CsrfGuard::getInstance();
        $csrf->guard();
    }

    private function hasHiddenFields($html): bool
    {
        return (bool) preg_match("/<input type=\"hidden\" name=\"CSRFName\" value=\"CSRFGuard_[0-9]+\" \/><input type=\"hidden\" name=\"CSRFToken\" value=\"[0-9a-zA-Z]+\" \/>/", $html);
    }
}