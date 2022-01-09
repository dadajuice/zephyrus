<?php namespace Zephyrus\Tests\Application\Session;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Session;

class SessionUnsecureConfigurationTest extends TestCase
{
    public function testUnsecureSessionInitialisation()
    {
        $this->expectException(InvalidArgumentException::class);
        ini_set('session.use_cookies', 0);
        ini_set('session.use_only_cookies', 0);
        Session::getInstance()->start();
    }
}
