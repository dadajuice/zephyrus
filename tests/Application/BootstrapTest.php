<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Bootstrap;

class BootstrapTest extends TestCase
{
    public function testGetFunctionPath()
    {
        $path = Bootstrap::getHelperFunctionsPath();
        $info = pathinfo($path, PATHINFO_BASENAME);
        self::assertEquals("functions.php", $info);
    }
}