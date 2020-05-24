<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Session;
use Zephyrus\Exceptions\UnauthorizedAccessException;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Security\Authorization;

class AuthorizationTest extends TestCase
{
    public function testModeWhitelist()
    {
        $req = new Request('http://test.local', 'POST');
        $auth = new Authorization($req);
        $auth->setMode(Authorization::MODE_WHITELIST);
        self::assertFalse($auth->isAuthorized('/'));
    }

    public function testModeBlacklist()
    {
        $req = new Request('http://test.local', 'POST');
        $auth = new Authorization($req);
        $auth->setMode(Authorization::MODE_BLACKLIST);
        self::assertTrue($auth->isAuthorized('/'));
    }

    public function testUrlArgument()
    {
        $req = new Request('http://test.local', 'GET');
        $auth = new Authorization($req);
        $auth->addRule('cook', function ($id) {
            return $id < 100;
        });
        $auth->protect('/product/{id}', Authorization::ALL, 'cook');
        self::assertFalse($auth->isAuthorized('/product/666'));
        self::assertTrue($auth->isAuthorized('/product/69'));
    }

    public function testSessionRule()
    {
        $req = new Request('http://test.local', 'GET');
        $auth = new Authorization($req);
        $auth->setMode(Authorization::MODE_WHITELIST);
        $auth->addSessionRule('admin', 'level', '777');
        $auth->protect('/users/{subpath}', Authorization::GET, 'admin');
        self::assertFalse($auth->isAuthorized('/users/insert'));
        $_SESSION['level'] = '777';
        //self::assertTrue($auth->isAuthorized('/users/4'));
        $auth->isAuthorized('/users/4');
        $_SESSION = [];
    }

    /**
     * @depends testSessionRule
     * @expectedException \Exception
     */
    public function testSessionRuleAlreadyDefined()
    {
        $req = new Request('http://test.local', 'GET');
        $auth = new Authorization($req);
        $auth->addSessionRule('admin', 'level', '777');
        $auth->addSessionRule('admin', 'level', '777');
    }

    /**
     * @depends testSessionRule
     * @expectedException \Exception
     */
    public function testSessionRuleAlreadyDefined2()
    {
        $req = new Request('http://test.local', 'GET');
        $auth = new Authorization($req);
        $auth->addSessionRule('admin', 'level', '777');
        $auth->addIpAddressRule('admin', '10.10.10.10');
    }

    /**
     * @depends testSessionRule
     * @expectedException \Exception
     */
    public function testSessionRuleAlreadyDefined3()
    {
        $req = new Request('http://test.local', 'GET');
        $auth = new Authorization($req);
        $auth->addSessionRule('admin', 'level', '777');
        $auth->addRule('admin', function () {
            return false;
        });
    }

    /**
     * @depends testSessionRule
     * @expectedException \Exception
     */
    public function testSessionRuleDefined()
    {
        $req = new Request('http://test.local', 'GET');
        $auth = new Authorization($req);
        $auth->protect('/users/*', Authorization::GET, 'admin');
        $auth->protect('/users/*', Authorization::GET, 'admin');
    }

    /**
     * @depends testSessionRule
     * @expectedException \Exception
     */
    public function testRequirementUndefined()
    {
        $req = new Request('http://test.local', 'GET');
        $auth = new Authorization($req);
        $auth->protect('/logs', Authorization::GET, 'invalid');
        $auth->isAuthorized('/logs');
    }

    /**
     * @depends testSessionRule
     */
    public function testMode()
    {
        $req = new Request('http://test.local', 'GET');
        $auth = new Authorization($req);
        $auth->setMode(Authorization::MODE_WHITELIST);
        self::assertEquals(Authorization::MODE_WHITELIST, $auth->getMode());
        $auth->setMode(Authorization::MODE_BLACKLIST);
        self::assertEquals(Authorization::MODE_BLACKLIST, $auth->getMode());
        $auth->setMode(Authorization::MODE_WHITELIST);
    }

    /**
     * @depends testSessionRule
     */
    public function testNotAuthorized()
    {
        $req = new Request('http://test.local', 'GET');
        $auth = new Authorization($req);
        $auth->setMode(Authorization::MODE_WHITELIST);
        self::assertFalse($auth->isAuthorized('/'));
    }

    public function testHome()
    {
        $req = new Request('http://test.local', 'GET');
        $auth = new Authorization($req);
        $auth->addSessionRule('admin', 'GOZU');
        $auth->protect('/', Authorization::ALL, 'admin');
        self::assertFalse($auth->isAuthorized('/'));
    }

    public function testNoRules()
    {
        $req = new Request('http://test.local', 'GET');
        $auth = new Authorization($req);
        $auth->setMode(Authorization::MODE_WHITELIST);
        $auth->addSessionRule('admin', 'GOZU');
        $auth->protect('/', Authorization::ALL, 'admin');
        $auth->protect('/test', Authorization::ALL, 'admin');
        self::assertFalse($auth->isAuthorized('/bob'));
    }

    /**
     * @depends testSessionRule
     */
    public function testIpRequirement()
    {
        $server['REMOTE_ADDR'] = '207.167.241.10';
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        $auth = new Authorization($req);
        $auth->addIpAddressRule('school', '207.167.241.10');
        $auth->protect('/bob', Authorization::ALL, 'school');
        self::assertTrue($auth->isAuthorized('/bob'));
    }

    /**
     * @depends testSessionRule
     */
    public function testCallbackRequirement()
    {
        $req = new Request('http://test.local', 'GET');
        $auth = new Authorization($req);
        $auth->addRule('public', function () {
            return true;
        });
        $auth->protect('/yup', Authorization::ALL, 'public');
        self::assertTrue($auth->isAuthorized('/yup'));
    }

    public function testException()
    {
        try {
            throw new UnauthorizedAccessException('/test', ['admin']);
        } catch (UnauthorizedAccessException $e) {
            self::assertEquals('/test', $e->getUri());
            self::assertTrue(in_array('admin', $e->getRequirements()));
        }
    }
}