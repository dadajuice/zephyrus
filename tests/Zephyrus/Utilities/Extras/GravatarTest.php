<?php namespace Zephyrus\Tests\Utilities\Extras;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\Gravatar;

class GravatarTest extends TestCase
{
    public function testIsAvailable()
    {
        $gravatar = new Gravatar("davidt2003@msn.com");
        $this->assertTrue($gravatar->isAvailable());
    }

    public function testIsUnavailable()
    {
        $gravatar = new Gravatar("davidt3@sn.com");
        $this->assertFalse($gravatar->isAvailable());
    }

    public function testGetUrl()
    {
        $gravatar = new Gravatar("davidt2003@msn.com");
        $url = $gravatar->getUrl();
        $this->assertTrue(str_contains($url, 'https://www.gravatar.com/avatar/'));
    }
}
