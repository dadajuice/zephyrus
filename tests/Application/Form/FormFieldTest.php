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

    public function testSimpleErrorPathing()
    {
        $field = new FormField("test", "error");
        $field->validate(Rule::integer("test"));
        self::assertFalse($field->verify());
        self::assertEquals("test", array_keys($field->getErrors())[0]);
    }

    public function testNestedErrorPathing()
    {
        $field = new FormField("student", ['name' => '', 'age' => 17]);
        $field->validate(Rule::nested("name", Rule::notEmpty("Student name must not be empty")));
        self::assertFalse($field->verify());
        self::assertTrue($field->isUsingNestedPathing());
        self::assertEquals("student.name", array_keys($field->getErrors())[0]);
    }

    public function testNoNestedErrorPathing()
    {
        $field = new FormField("student", ['name' => '', 'age' => 17]);
        $field->setUseNestedPathing(false);
        $field->validate(Rule::nested("name", Rule::notEmpty("Student name must not be empty")));
        self::assertFalse($field->verify());
        self::assertFalse($field->isUsingNestedPathing());
        self::assertEquals("student", array_keys($field->getErrors())[0]);
    }

    public function testDeeperNestedErrorPathing()
    {
        $field = new FormField("student", [
            'name' => 'Bob Lewis',
            'age' => 17,
            'scoring' => [
                'highest' => 90,
                'lowest' => "oops"
            ]
        ]);
        $field->validate(Rule::nested("name", Rule::notEmpty("Student name must not be empty")));
        $field->validate(Rule::nested("scoring", Rule::nested("lowest", Rule::integer("Low score invalid"))));
        self::assertFalse($field->verify());
        self::assertTrue($field->isUsingNestedPathing());
        self::assertEquals("student.scoring.lowest", array_keys($field->getErrors())[0]);
        self::assertEquals("Low score invalid", $field->getErrorMessages()[0]);

        $field = new FormField("student", [
            'name' => 'Bob Lewis',
            'age' => 17,
            'scoring' => [
                'highest' => 90,
                'lowest' => "oops"
            ]
        ]);
        $field->validate(Rule::nested("scoring", [
            'highest' => Rule::integer("High score invalid"),
            'lowest' =>  Rule::integer("Low score invalid")
        ]));
        self::assertFalse($field->verify());
        self::assertEquals("student.scoring.lowest", array_keys($field->getErrors())[0]);
        self::assertEquals("Low score invalid", $field->getErrorMessages()[0]);
    }

    public function testEvenDeeperNestedErrorPathing()
    {
        $field = new FormField("student", [
            'name' => 'Bob Lewis',
            'age' => 17,
            'scoring' => [
                'highest' => [
                    'first' => 96,
                    'second' => "Oups",
                    'third' => 85
                ],
                'lowest' => 45
            ]
        ]);
        $field->validate(Rule::nested("scoring", [
            'highest' => [
                'first' => Rule::integer("First High score invalid"),
                'second' => Rule::integer("Second High score invalid"),
                'third' => Rule::integer("Third High score invalid"),
            ],
            'lowest' =>  Rule::integer("Low score invalid")
        ]));
        self::assertFalse($field->verify());
        self::assertEquals("student.scoring.highest.second", array_keys($field->getErrors())[0]);
        self::assertEquals("Second High score invalid", $field->getErrorMessages()[0]);
    }

    public function testIndexNestedErrorPathing()
    {
        $field = new FormField("student", [
            'name' => 'Bob Lewis',
            'age' => 17,
            'scoring' => [
                'highest' => [96, "Oups", 85],
                'lowest' => 45
            ]
        ]);
        $field->validate(Rule::nested("scoring", [
            'highest' => Rule::all(Rule::integer("High score invalid")),
            'lowest' =>  Rule::integer("Low score invalid")
        ]));
        self::assertFalse($field->verify());
        self::assertEquals("student.scoring.highest.1", array_keys($field->getErrors())[0]);
        self::assertEquals("High score invalid", $field->getErrorMessages()[0]);
    }

    public function testDeeperIndexNestedErrorPathing()
    {
        $field = new FormField("student", [
            'name' => 'Bob Lewis',
            'age' => 17,
            'classes' => [
                (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => 3],
                (object) ['title' => '', 'description' => 'Lorem', 'hours' => 3],
                (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => 3]
            ],
            'scoring' => [
                'highest' => [96, 90, 85],
                'lowest' => 45
            ]
        ]);
        $field->validate(Rule::nested("scoring", [
            'highest' => Rule::all(Rule::integer("High score invalid")),
            'lowest' =>  Rule::integer("Low score invalid")
        ]));
        $field->validate(Rule::nested("classes", Rule::all(Rule::nested("title", Rule::notEmpty("Title invalid")))));
        self::assertFalse($field->verify());
        self::assertEquals("student.classes.1.title", array_keys($field->getErrors())[0]);
        self::assertEquals("Title invalid", $field->getErrorMessages()[0]);
    }

    public function testDeeperIndexMultipleAllNestedErrorPathing()
    {
        $field = new FormField("student", [
            'name' => 'Bob Lewis',
            'age' => 17,
            'classes' => [
                (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => 3],
                (object) ['title' => '', 'description' => 'Lorem', 'hours' => 3],
                (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => 3]
            ],
            'scoring' => [
                'highest' => [96, 90, 85],
                'lowest' => 45
            ]
        ]);
        $field->validate(Rule::nested("scoring", [
            'highest' => Rule::all(Rule::integer("High score invalid")),
            'lowest' =>  Rule::integer("Low score invalid")
        ]));
        $field->validate(Rule::nested("classes", Rule::all([
            'title' => Rule::notEmpty("Title invalid"),
            'description' => Rule::notEmpty("Description invalid"),
            'hours' => Rule::integer("Hours invalid")
        ])));
        self::assertFalse($field->verify());
        self::assertEquals("student.classes.1.title", array_keys($field->getErrors())[0]);
        self::assertEquals("Title invalid", $field->getErrorMessages()[0]);
    }
}
