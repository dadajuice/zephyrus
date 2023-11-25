<?php namespace Zephyrus\Tests\Security\IntrusionDetection;

use PHPUnit\Framework\TestCase;
use Zephyrus\Security\IntrusionDetection\IntrusionCache;
use Zephyrus\Security\IntrusionDetection\IntrusionRuleLoader;

class IntrusionCacheTest extends TestCase
{
    public function testEmptyCache()
    {
        $cache = new IntrusionCache();
        $cache->clear();
        $intrusionRules = $cache->getRules();
        self::assertEmpty($intrusionRules);
    }

    public function testSaveCache()
    {
        $cache = new IntrusionCache();
        $cache->clear();
        $cache->cache((new IntrusionRuleLoader())->loadFromFile());
        $res = $cache->getRules();
        self::assertCount(74, $res);
        self::assertTrue(is_object($res[73]));
        self::assertTrue(property_exists($res[73], 'impact'));
        self::assertTrue(property_exists($res[73], 'id'));
        self::assertTrue(property_exists($res[73], 'rule'));
        self::assertTrue(property_exists($res[73], 'description'));
        self::assertTrue(property_exists($res[73], 'tags'));
        self::assertEquals("Detects SQL comment filter evasion", $res[73]->description);
    }

    /**
     * @depends testSaveCache
     */
    public function testReadCache()
    {
        $cache = new IntrusionCache();
        $res = $cache->getRules();
        $cache->clear();
        self::assertCount(74, $res);
        self::assertTrue(is_object($res[73]));
        self::assertTrue(property_exists($res[73], 'impact'));
        self::assertTrue(property_exists($res[73], 'id'));
        self::assertTrue(property_exists($res[73], 'rule'));
        self::assertTrue(property_exists($res[73], 'description'));
        self::assertTrue(property_exists($res[73], 'tags'));
        self::assertEquals("Detects SQL comment filter evasion", $res[73]->description);
    }
}
