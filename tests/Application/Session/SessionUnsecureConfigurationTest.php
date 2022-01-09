<?php namespace Zephyrus\Tests\Application\Session;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Session;

class SessionUnsecureConfigurationTest extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        // Reapply default setting for future session initialisation
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
    }

    public function testUnsecureSessionInitialisation()
    {
        $this->expectException(InvalidArgumentException::class);
        ini_set('session.use_cookies', 0);
        ini_set('session.use_only_cookies', 0);
        Session::getInstance()->start();
    }
}
