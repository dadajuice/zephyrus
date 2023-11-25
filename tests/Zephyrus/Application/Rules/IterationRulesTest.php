<?php namespace Zephyrus\Tests\Application\Rules;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Rule;

class IterationRulesTest extends TestCase
{
    public function testAssociativeArray()
    {
        $rule = Rule::associativeArray("not assoc");
        self::assertFalse($rule->isValid([1, 2, 3, 4, 5, 6]));
        self::assertFalse($rule->isValid(["Bob", "Lewis", 4, 3.5, "Toto"]));
        self::assertTrue($rule->isValid([
            'name' => 'Bob',
            'age' => 30
        ]));
        self::assertTrue($rule->isValid([
            'name' => 'Bob',
            2 => 'test',
            'age' => 30
        ]));
    }

    public function testArrayAll()
    {
        $rule = Rule::each([Rule::integer()]);
        self::assertTrue($rule->isValid([1, 2, 3, 4, 5, 6]));
        self::assertFalse($rule->isValid([1, 2, "error", 4, 5, 6]));
    }

    public function testArrayAllObject()
    {
        $rule = Rule::each([
            Rule::nested('title', [Rule::notEmpty("Title invalid")]),
            Rule::nested('description', [Rule::notEmpty("Description invalid")]),
            Rule::nested('hours', [Rule::notEmpty("Hours invalid")]),
        ]);
        self::assertTrue($rule->isValid([
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => 3],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => 3],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => 3]
        ]));
        self::assertFalse($rule->isValid([
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => 3],
            (object) ['title' => '', 'description' => 'Lorem', 'hours' => 3],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => 3]
        ]));
    }

    public function testArrayAllNestedObject()
    {
        $rule = Rule::each([
            Rule::nested('title', [Rule::notEmpty("Title invalid")]),
            Rule::nested('description', [Rule::notEmpty("Description invalid")]),
            Rule::nested('hours', [
                Rule::nested('time', [Rule::integer("Time invalid")])
            ])
        ]);
        self::assertTrue($rule->isValid([
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => ['block' => 4, 'time' => 300]],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => ['block' => 4, 'time' => 300]],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => ['block' => 4, 'time' => 300]]
        ]));
        self::assertFalse($rule->isValid([
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => ['block' => 4, 'time' => 300]],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => ['block' => 4, 'time' => "Oups"]],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => ['block' => 4, 'time' => 300]]
        ]));
    }

    public function testArrayAllNestedObject2()
    {
        $rule = Rule::each([
            Rule::nested('title', [Rule::notEmpty("Title invalid")]),
            Rule::nested('description', [Rule::notEmpty("Description invalid")]),
            Rule::nested('hours', [
                Rule::array("Must be array"),
                Rule::each([
                    Rule::nested('time', [Rule::integer("Time invalid")])
                ])
            ])
        ]);

        self::assertTrue($rule->isValid([
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => [['block' => 4, 'time' => 300], ['block' => 4, 'time' => 300]]],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => [['block' => 4, 'time' => 300], ['block' => 4, 'time' => 300]]],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => [['block' => 4, 'time' => 300], ['block' => 4, 'time' => 300]]]
        ]));
        self::assertFalse($rule->isValid([
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => [['block' => 4, 'time' => 300], ['block' => 4, 'time' => 300]]],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => [['block' => 4, 'time' => 300], ['block' => 4, 'time' => 300]]],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => [['block' => 4, 'time' => 300], ['block' => 4, 'time' => "Oups"]]]
        ]));
    }

    public function testArrayAllNestedObject3()
    {
        $rule = Rule::each([
            Rule::nested('title', [Rule::notEmpty("Title invalid")]),
            Rule::nested('description', [Rule::notEmpty("Description invalid")]),
            Rule::nested('hours', [
                Rule::each([
                    Rule::nested('time', [
                        Rule::integer("Time invalid"),
                        Rule::range(0, 300, "Time invalid")
                    ])
                ])
            ])
        ]);
        self::assertTrue($rule->isValid([
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => [['block' => 4, 'time' => 300], ['block' => 4, 'time' => 300]]],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => [['block' => 4, 'time' => 300], ['block' => 4, 'time' => 300]]],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => [['block' => 4, 'time' => 300], ['block' => 4, 'time' => 300]]]
        ]));
        self::assertFalse($rule->isValid([
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => [['block' => 4, 'time' => 300], ['block' => 4, 'time' => 300]]],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => [['block' => 4, 'time' => 300], ['block' => 4, 'time' => 300]]],
            (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => [['block' => 4, 'time' => 300], ['block' => 4, 'time' => 500]]]
        ]));
    }

    public function testArrayNotEmpty()
    {
        $rule = Rule::arrayNotEmpty();
        self::assertTrue($rule->isValid([1, 2, 3]));
        self::assertFalse($rule->isValid([]));
        self::assertFalse($rule->isValid("oui"));
        self::assertFalse($rule->isValid(null));
    }

    public function testIsInArray()
    {
        $rule = Rule::inArray(["a", "b", "c"], "err");
        self::assertTrue($rule->isValid("b"));
        self::assertFalse($rule->isValid("e"));
    }

    public function testIsNotInArray()
    {
        $rule = Rule::notInArray(["a", "b", "c"], "err");
        self::assertTrue($rule->isValid("d"));
        self::assertFalse($rule->isValid("b"));
    }

    public function testIsOnlyWithin()
    {
        $rule = Rule::onlyWithin(['a', 'b', 'c']);
        self::assertTrue($rule->isValid(['a']));
        self::assertTrue($rule->isValid(['a', 'b']));
        self::assertTrue($rule->isValid(['a', 'b', 'c']));
        self::assertTrue($rule->isValid(['a', 'a']));
        self::assertTrue($rule->isValid('a'));
        self::assertTrue($rule->isValid('c'));
        self::assertFalse($rule->isValid(['a', 'b', 'c', 'd']));
        self::assertFalse($rule->isValid(['d', 'c', 'b', 'a']));
        self::assertFalse($rule->isValid(['g']));
        self::assertFalse($rule->isValid(['g', 'a']));
        self::assertFalse($rule->isValid(['a', 'g']));
        self::assertFalse($rule->isValid('d'));
    }

    public function testNestedSimpleArray()
    {
        $rule = Rule::nested('age', [Rule::integer()]);
        self::assertTrue($rule->isValid(['name' => 'Bob', 'age' => 23]));
        self::assertFalse($rule->isValid(['name' => 'Bob']));
        self::assertFalse($rule->isValid(['age' => 'Bob']));
    }

    public function testNestedSimpleObject()
    {
        $rule = Rule::nested('age', [Rule::integer()]);
        self::assertTrue($rule->isValid((object) ['name' => 'Bob', 'age' => 23]));
        self::assertFalse($rule->isValid((object) ['name' => 'Bob']));
        self::assertFalse($rule->isValid((object) ['age' => 'Bob']));
    }

    public function testNestedComplexArray()
    {
        $rule = Rule::nested('student', [Rule::nested('name', [Rule::notEmpty()])]);
        self::assertTrue($rule->isValid(['student' => ['id' => 3, 'name' => 'Rolan Balesque', 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid(['student' => ['id' => 3, 'name' => '', 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid(['student' => ['id' => 3, 'username' => 'mbalesque']]));
    }

    public function testNestedComplexObject()
    {
        $rule = Rule::nested('student', [Rule::nested('name', [Rule::notEmpty()])]);
        self::assertTrue($rule->isValid((object) ['student' => (object) ['id' => 3, 'name' => 'Rolan Balesque', 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid((object) ['student' => (object) ['id' => 3, 'name' => '', 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid((object) ['student' => (object) ['id' => 3, 'username' => 'mbalesque']]));
    }

    public function testNestedMixedArrayObject()
    {
        $rule = Rule::nested('student', [Rule::nested('name', [Rule::notEmpty()])]);
        self::assertTrue($rule->isValid(['student' => (object)['id' => 3, 'name' => 'Rolan Balesque', 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid(['student' => (object)['id' => 3, 'name' => '', 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid(['student' => (object)['id' => 3, 'username' => 'mbalesque']]));
    }

    public function testNestedSimpleWithMultipleRules()
    {
        $rule = Rule::nested('test', [
            Rule::notEmpty(),
            Rule::integer(),
            Rule::range(0, 100)
        ]);
        $row = ['test' => 3];
        self::assertTrue($rule->isValid($row));

        $row = ['test' => 300];
        self::assertFalse($rule->isValid($row));
    }
}
