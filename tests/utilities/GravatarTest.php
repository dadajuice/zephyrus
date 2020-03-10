<?php namespace Zephyrus\Tests;


use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\Gravatar;

class GravatarTest extends TestCase
{
    public function testIsAvailable()
    {
        $gravatar = new Gravatar("davidt2003@msn.com");
        self::assertTrue($gravatar->isAvailable());
    }

    public function testIsUnavailable()
    {
        $gravatar = new Gravatar("davidt3@sn.com");
        self::assertFalse($gravatar->isAvailable());
    }

    public function testGetUrl()
    {
        $gravatar = new Gravatar("davidt2003@msn.com");
        $url = $gravatar->getUrl();
        self::assertTrue(strpos($url, 'https://www.gravatar.com/avatar/') !== false);
    }
}
