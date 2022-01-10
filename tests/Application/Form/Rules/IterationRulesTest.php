<?php namespace Zephyrus\Tests\Application\Form\Rules;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Rule;

class IterationRulesTest extends TestCase
{
    public function testArrayAll()
    {
        $rule = Rule::all(Rule::integer(), "not all integers");
        self::assertTrue($rule->isValid([1, 2, 3, 4, 5, 6]));
        self::assertFalse($rule->isValid([1, 2, "error", 4, 5, 6]));
    }

    public function testArrayAllObject()
    {
        $rule = Rule::all([
            'title' => Rule::notEmpty("Title invalid"),
            'description' => Rule::notEmpty("Description invalid"),
            'hours' => Rule::integer("Hours invalid")
        ], "not all ok");
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
        $rule = Rule::all([
            'title' => Rule::notEmpty("Title invalid"),
            'description' => Rule::notEmpty("Description invalid"),
            'hours' => Rule::nested('time', Rule::integer("Time invalid"))
        ], "not all ok");
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
        $rule = Rule::all([
            'title' => Rule::notEmpty("Title invalid"),
            'description' => Rule::notEmpty("Description invalid"),
            'hours' => Rule::all(Rule::nested('time', Rule::integer("Time invalid")))
        ], "not all ok");
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
        $rule = Rule::all([
            'title' => Rule::notEmpty("Title invalid"),
            'description' => Rule::notEmpty("Description invalid"),
            'hours' => Rule::all([
                'time' => [
                    Rule::integer("Time invalid"),
                    Rule::range(0, 300, "Time invalid")
                ]
            ])
        ], "not all ok");
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

    public function testArrayAllNotArray()
    {
        $rule = Rule::all(Rule::integer(), "not all integers");
        self::assertFalse($rule->isValid("Invalid"));
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
        $rule = Rule::nested('age', Rule::integer());
        self::assertTrue($rule->isValid(['name' => 'Bob', 'age' => 23]));
        self::assertFalse($rule->isValid(['name' => 'Bob']));
        self::assertFalse($rule->isValid(['age' => 'Bob']));
    }

    public function testNestedSimpleObject()
    {
        $rule = Rule::nested('age', Rule::integer());
        self::assertTrue($rule->isValid((object) ['name' => 'Bob', 'age' => 23]));
        self::assertFalse($rule->isValid((object) ['name' => 'Bob']));
        self::assertFalse($rule->isValid((object) ['age' => 'Bob']));
    }

    public function testNestedComplexArray()
    {
        $rule = Rule::nested('student', Rule::nested('name', Rule::notEmpty()));
        self::assertTrue($rule->isValid(['student' => ['id' => 3, 'name' => 'Rolan Balesque', 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid(['student' => ['id' => 3, 'name' => '', 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid(['student' => ['id' => 3, 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid(['not_student' => 'test']));
    }

    public function testNestedComplexObject()
    {
        $rule = Rule::nested('student', Rule::nested('name', Rule::notEmpty()));
        self::assertTrue($rule->isValid((object) ['student' => (object) ['id' => 3, 'name' => 'Rolan Balesque', 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid((object) ['student' => (object) ['id' => 3, 'name' => '', 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid((object) ['student' => (object) ['id' => 3, 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid((object) ['not_student' => 'test']));
    }

    public function testNestedMixedArrayObject()
    {
        $rule = Rule::nested('student', Rule::nested('name', Rule::notEmpty()));
        self::assertTrue($rule->isValid(['student' => (object)['id' => 3, 'name' => 'Rolan Balesque', 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid(['student' => (object)['id' => 3, 'name' => '', 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid(['student' => (object)['id' => 3, 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid(['not_student' => 'test']));
    }

    public function testNestedMixedMessages()
    {
        $rule = Rule::nested('student', Rule::nested('name', Rule::notEmpty("Name is empty"), "Invalid name key"), "invalid student key");
        $rule->isValid(['student' => (object)['id' => 3, 'name' => '', 'username' => 'mbalesque']]);
        self::assertEquals("Name is empty", $rule->getErrorMessage());

        $rule = Rule::nested('student', Rule::nested('name', Rule::notEmpty("Name is empty"), "Invalid name key"), "invalid student key");
        $rule->isValid(['student' => (object)['id' => 3, 'username' => 'mbalesque']]);
        self::assertEquals("Invalid name key", $rule->getErrorMessage());

        $rule = Rule::nested('student', Rule::nested('name', Rule::notEmpty("Name is empty"), "Invalid name key"), "invalid student key");
        $rule->isValid(['not_student' => 'test']);
        self::assertEquals("invalid student key", $rule->getErrorMessage());
    }

    public function testNestedComplexArrayMultiple()
    {
        $rule = Rule::nested('student', [
            'name' => Rule::notEmpty(),
            'id' => Rule::integer()
        ]);
        self::assertTrue($rule->isValid(['student' => ['id' => 3, 'name' => 'Rolan Balesque', 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid(['student' => ['id' => 'e', 'name' => 'Bob', 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid(['student' => ['id' => 3, 'username' => 'mbalesque']]));
        self::assertFalse($rule->isValid(['not_student' => 'test']));
    }

    public function testNestedComplexArrayMultiple2()
    {
        $rule = Rule::nested('student', [
            'name' => Rule::notEmpty(),
            'id' => Rule::integer(),
            'main_course' => [
                'id' => Rule::integer(),
                'class' => Rule::notEmpty()
            ],
            'teachers' => Rule::all(Rule::name())
        ]);

        $row = ['student' => [
            'id' => 3,
            'name' => 'Rolan Balesque',
            'main_course' => (object) [
                'id' => 500,
                'class' => 'Computer'
            ],
            'teachers' => ['Bob', 'Rolan', 'Dan', 'Claire']
        ]];
        self::assertTrue($rule->isValid($row));

        $row = ['student' => [
            'id' => 3,
            'name' => 'Rolan Balesque',
            'main_course' => (object) [
                'id' => "ERROR",
                'class' => 'Computer'
            ],
            'teachers' => ['Bob', 'Rolan', 'Dan', 'Claire']
        ]];
        self::assertFalse($rule->isValid($row));


        $rule = Rule::nested('student', [
            'name' => Rule::notEmpty(),
            'id' => Rule::integer(),
            'main_course' => [
                'id' => Rule::integer(),
                'class' => Rule::notEmpty(),
                'info' => [
                    'description' => Rule::notEmpty("NANI Y"),
                    'number' => Rule::notEmpty()
                ]
            ],
            'teachers' => Rule::all(Rule::name())
        ]);
        $row = ['student' => [
            'id' => 3,
            'name' => 'Rolan Balesque',
            'main_course' => (object) [
                'id' => 200,
                'class' => 'Computer',
                'info' => [
                    'description' => 'Lorem ipsum',
                    'number' => '420-2S4-SU'
                ]
            ],
            'teachers' => ['Bob', 'Rolan', 'Dan', 'Claire']
        ]];
        self::assertTrue($rule->isValid($row));

        $row = ['student' => [
            'id' => 3,
            'name' => 'Rolan Balesque',
            'main_course' => (object) [
                'id' => 200,
                'class' => 'Computer',
                'info' => [
                    'description' => '',
                    'number' => '420-2S4-SU'
                ]
            ],
            'teachers' => ['Bob', 'Rolan', 'Dan', 'Claire']
        ]];
        self::assertFalse($rule->isValid($row));
        self::assertEquals("NANI Y", $rule->getErrorMessage());
    }

    public function testAllSimpleWithMultipleRules()
    {
        $rule = Rule::all([
            Rule::notEmpty(),
            Rule::integer(),
            Rule::range(0, 100)
        ]);
        $row = [3, 9, 10, 99, 80];
        self::assertTrue($rule->isValid($row));

        $row = [3, 9, 10, 400, 80];
        self::assertFalse($rule->isValid($row));
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

    public function testNestedComplexArrayMultiple3()
    {
        $rule = Rule::nested('student', [
            'name' => Rule::notEmpty(),
            'id' => Rule::integer(),
            'main_course' => [
                'id' => [Rule::integer(), Rule::range(0, 100, "NANI Z")],
                'class' => Rule::notEmpty(),
                'info' => [
                    'description' => Rule::notEmpty(),
                    'number' => Rule::notEmpty()
                ]
            ],
            'teachers' => Rule::all(Rule::name())
        ]);
        $row = ['student' => [
            'id' => 3,
            'name' => 'Rolan Balesque',
            'main_course' => (object) [
                'id' => 80,
                'class' => 'Computer',
                'info' => [
                    'description' => 'Lorem ipsum',
                    'number' => '420-2S4-SU'
                ]
            ],
            'teachers' => ['Bob', 'Rolan', 'Dan', 'Claire']
        ]];
        self::assertTrue($rule->isValid($row));

        $row = ['student' => [
            'id' => 3,
            'name' => 'Rolan Balesque',
            'main_course' => (object) [
                'id' => 250, // Trigger
                'class' => 'Computer',
                'info' => [
                    'description' => 'Lorem ipsum',
                    'number' => '420-2S4-SU'
                ]
            ],
            'teachers' => ['Bob', 'Rolan', 'Dan', 'Claire']
        ]];
        self::assertFalse($rule->isValid($row));
        self::assertEquals("NANI Z", $rule->getErrorMessage());
    }
}