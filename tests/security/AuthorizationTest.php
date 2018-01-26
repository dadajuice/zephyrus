<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Exceptions\UnauthorizedAccessException;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Security\Authorization;

class AuthorizationTest extends TestCase
{
    public function testAuthorizeMethodNotDefined()
    {
        $req = new Request('http://test.local', 'POST');
        RequestFactory::set($req);
        $auth = new Authorization();
        $auth->setMode(Authorization::MODE_WHITELIST);
        self::assertFalse($auth->isAuthorized('/'));
    }

    public function testSessionRule()
    {
        $req = new Request('http://test.local', 'GET');
        RequestFactory::set($req);
        $auth = new Authorization();
        $auth->addSessionRequirement('admin', 'level', '777');
        $auth->protect('/users/*', Authorization::GET, 'admin');
        self::assertFalse($auth->isAuthorized('/users/insert'));
        $_SESSION['level'] = '777';
        self::assertTrue($auth->isAuthorized('/users/insert'));
        $_SESSION = [];
    }

    /**
     * @depends testSessionRule
     * @expectedException \Exception
     */
    public function testSessionRuleAlreadyDefined()
    {
        $auth = new Authorization();
        $auth->addSessionRequirement('admin', 'level', '777');
        $auth->addSessionRequirement('admin', 'level', '777');
    }

    /**
     * @depends testSessionRule
     * @expectedException \Exception
     */
    public function testSessionRuleAlreadyDefined2()
    {
        $auth = new Authorization();
        $auth->addSessionRequirement('admin', 'level', '777');
        $auth->addIpAddressRequirement('admin', '10.10.10.10');
    }

    /**
     * @depends testSessionRule
     * @expectedException \Exception
     */
    public function testSessionRuleAlreadyDefined3()
    {
        $auth = new Authorization();
        $auth->addSessionRequirement('admin', 'level', '777');
        $auth->addRequirement('admin', function () {
            return false;
        });
    }

    /**
     * @depends testSessionRule
     * @expectedException \Exception
     */
    public function testSessionRuleDefined()
    {
        $auth = new Authorization();
        $auth->protect('/users/*', Authorization::GET, 'admin');
        $auth->protect('/users/*', Authorization::GET, 'admin');
    }

    /**
     * @depends testSessionRule
     * @expectedException \Exception
     */
    public function testRequirementUndefined()
    {
        $auth = new Authorization();
        $auth->protect('/logs', Authorization::GET, 'invalid');
        $auth->isAuthorized('/logs');
    }

    /**
     * @depends testSessionRule
     */
    public function testMode()
    {
        $auth = new Authorization();
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
        $auth = new Authorization();
        $auth->setMode(Authorization::MODE_WHITELIST);
        self::assertFalse($auth->isAuthorized('/'));
    }

    public function testHome()
    {
        $auth = new Authorization();
        $auth->addSessionRequirement('admin', 'GOZU');
        $auth->protect('/', Authorization::ALL, 'admin');
        self::assertFalse($auth->isAuthorized('/'));
    }

    public function testNoRules()
    {
        $auth = new Authorization();
        $auth->setMode(Authorization::MODE_WHITELIST);
        $auth->addSessionRequirement('admin', 'GOZU');
        $auth->protect('/', Authorization::ALL, 'admin');
        $auth->protect('/test', Authorization::ALL, 'admin');
        self::assertFalse($auth->isAuthorized('/bob'));
    }

    /**
     * @depends testSessionRule
     */
    public function testIpRequirement()
    {
        $auth = new Authorization();
        $auth->addIpAddressRequirement('school', '207.167.241.10');
        $auth->protect('/bob', Authorization::ALL, 'school');
        $server['REMOTE_ADDR'] = '207.167.241.10';
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);
        self::assertTrue($auth->isAuthorized('/bob'));
        $req = new Request('http://test.local', 'GET');
        RequestFactory::set($req);
    }

    /**
     * @depends testSessionRule
     */
    public function testCallbackRequirement()
    {
        $auth = new Authorization();
        $auth->addRequirement('public', function () {
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