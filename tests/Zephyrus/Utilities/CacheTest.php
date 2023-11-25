<?php namespace Zephyrus\Tests\Utilities;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\Cache;

class CacheTest extends TestCase
{
    public function testApcuAvailable()
    {
        // Make sure the "apc.enable_cli=on" directive is in the php.ini file. This cannot be set using the ini_set
        // function.
        self::assertTrue(Cache::isAvailable());
    }

    public function testCacheValue()
    {
        $cache = new Cache("TEST");
        $cache->cache('testing');
        self::assertEquals("testing", (string) apcu_fetch("TEST"));
    }

    public function testReadUncachedValue()
    {
        $cache = new Cache("BOB");
        self::assertFalse($cache->exists());
        self::assertEquals(null, $cache->read());
    }

    #[Depends("testCacheValue")]
    public function testReadCachedValue()
    {
        $cache = new Cache("TEST");
        self::assertTrue($cache->exists());
        self::assertEquals("testing", $cache->read());
    }

    #[Depends("testCacheValue")]
    public function testClearCachedValue()
    {
        $cache = new Cache("TEST");
        $cache->remove();
        self::assertFalse($cache->exists());
        self::assertEquals(null, $cache->read());
        self::assertFalse(apcu_fetch("TEST"));
    }
}
