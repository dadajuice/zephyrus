<?php namespace Zephyrus\Tests\utilities;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\ComposerPackage;

class ComposerPackageTest extends TestCase
{
    public function testGetPackages()
    {
        $array = ComposerPackage::getPackages();
        $versions = ComposerPackage::getVersions();
        self::assertEquals(3, count($array));
        self::assertEquals("2.7.6", ComposerPackage::getVersion('pug-php/pug'));
        self::assertEquals("2.7.6", $versions['pug-php/pug']);
        self::assertEquals("2.7.6", $array['pug-php/pug']->version);
        self::assertEquals("2.7.6", ComposerPackage::getPackage('pug-php/pug')->version);
    }
}
