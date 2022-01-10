<?php namespace Zephyrus\Tests\Application\Form;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\FormField;
use Zephyrus\Application\Rule;

class FormFieldTest extends TestCase
{
    public function testInitialization()
    {
        $field = new FormField("test", "123");
        self::assertEquals("test", $field->getName());
        self::assertEquals("123", $field->getValue());
        self::assertFalse($field->hasError());
        self::assertEmpty($field->getErrorMessages());
        self::assertTrue($field->isOptionalOnEmpty());

        $field = new FormField("ids[]", [1, 2, 3]);
        self::assertEquals("ids[]", $field->getName());
        self::assertEquals([1, 2, 3], $field->getValue());
        self::assertFalse($field->hasError());
        self::assertEmpty($field->getErrorMessages());
        self::assertTrue($field->isOptionalOnEmpty());
    }

    public function testSimpleValidation()
    {
        $field = new FormField("test", 35);
        $field->validate(Rule::integer("test"));
        self::assertTrue($field->verify());
        self::assertFalse($field->hasError());
        self::assertEmpty($field->getErrorMessages());

        $field = new FormField("test", "error");
        $field->validate(Rule::integer("test"));
        self::assertFalse($field->verify());
        self::assertTrue($field->hasError());
        self::assertCount(1, $field->getErrorMessages());
        self::assertEquals("test", $field->getErrorMessages()[0]);
    }
}
