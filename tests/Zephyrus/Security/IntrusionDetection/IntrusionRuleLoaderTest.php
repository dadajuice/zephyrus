<?php namespace Zephyrus\Tests\Security\IntrusionDetection;

use PHPUnit\Framework\TestCase;
use Zephyrus\Security\IntrusionDetection\IntrusionRuleLoader;

class IntrusionRuleLoaderTest extends TestCase
{
    public function testLoadFromDefaultFile()
    {
        $loader = new IntrusionRuleLoader();
        $res = $loader->loadFromFile();
        self::assertCount(74, $res);
        self::assertTrue(is_object($res[73]));
        self::assertTrue(property_exists($res[73], 'impact'));
        self::assertTrue(property_exists($res[73], 'id'));
        self::assertTrue(property_exists($res[73], 'rule'));
        self::assertTrue(property_exists($res[73], 'description'));
        self::assertTrue(property_exists($res[73], 'tags'));
        self::assertEquals("Detects SQL comment filter evasion", $res[73]->description);
    }

    public function testLoadFromCustomFile()
    {
        $loader = new IntrusionRuleLoader(ROOT_DIR . '/lib/custom_filter_rules.json');
        $res = $loader->loadFromFile();
        self::assertCount(1, $res);
        self::assertTrue(is_object($res[0]));
        self::assertTrue(property_exists($res[0], 'impact'));
        self::assertTrue(property_exists($res[0], 'id'));
        self::assertTrue(property_exists($res[0], 'rule'));
        self::assertTrue(property_exists($res[0], 'description'));
        self::assertTrue(property_exists($res[0], 'tags'));
        self::assertEquals("custom", $res[0]->description);
    }

    public function testLoadFromUnavailableCustomFile()
    {
        self::expectException(\InvalidArgumentException::class);
        $loader = new IntrusionRuleLoader(ROOT_DIR . '/lib/custom_filter_rules2.json');
        $loader->loadFromFile();
    }

    public function testLoadFromNonParsableCustomFile()
    {
        self::expectException(\RuntimeException::class);
        $loader = new IntrusionRuleLoader(ROOT_DIR . '/secrets.txt');
        $loader->loadFromFile();
    }
}
