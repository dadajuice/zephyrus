<?php namespace Zephyrus\Tests\Utilities;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\ComposerPackage;

class ComposerPackageTest extends TestCase
{
    public function testGetPackages()
    {
        $array = ComposerPackage::getPackages();
        $versions = ComposerPackage::getVersions();
        $this->assertCount(3, $array);
        $this->assertEquals("2.7.6", ComposerPackage::getVersion('pug-php/pug'));
        $this->assertEquals("2.7.6", $versions['pug-php/pug']);
        $this->assertEquals("2.7.6", $array['pug-php/pug']->version);
        $this->assertEquals("2.7.6", ComposerPackage::getPackage('pug-php/pug')->version);
    }
}
