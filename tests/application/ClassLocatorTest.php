<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\ClassLocator;

class ClassLocatorTest extends TestCase
{
    public function testDefinedNamespace()
    {
        $namespaces = ClassLocator::getDefinedNamespaces();
        self::assertTrue(key_exists('Zephyrus\\', $namespaces));
    }

    public function testClassNameInNamespace()
    {
        $locator = new ClassLocator('Zephyrus\\Application\\');
        $found = strpos($locator->getDirectory(), '/src/Zephyrus/Application') !== false;
        self::assertTrue($found);
        $classes = $locator->getClasses();
        self::assertTrue(in_array('Zephyrus\Application\ClassLocator', $classes));
    }

    /**
     * @expectedException \Exception
     */
    public function testInvalidNamespace()
    {
        new ClassLocator('test90287349\\');
    }
}