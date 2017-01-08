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
        //$classes = ClassLocator::getClassesInNamespace('Zephyrus\\Application\\');
        //print_r($classes);
        //self::assertTrue(key_exists('Zephyrus\\', $classes));
    }

    /**
     * @expectedException \Exception
     */
    public function testInvalidNamespace()
    {
        ClassLocator::getClassesInNamespace('test90287349\\');
    }
}