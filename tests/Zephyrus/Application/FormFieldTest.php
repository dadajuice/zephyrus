<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\FormField;
use Zephyrus\Application\Rule;

class FormFieldTest extends TestCase
{
    public function testInitialization()
    {
        $field = new FormField("amount", "12.30");
        self::assertEquals("amount", $field->getName());
        self::assertEquals("12.30", $field->getValue());
        self::assertFalse($field->hasError());
        self::assertEmpty($field->getErrorMessages());
        self::assertEmpty($field->getErrors());

        $field = new FormField("product_ids[]", [1, 2, 3]);
        self::assertEquals("product_ids[]", $field->getName());
        self::assertEquals([1, 2, 3], $field->getValue());
        self::assertFalse($field->hasError());
        self::assertEmpty($field->getErrorMessages());
        self::assertEmpty($field->getErrors());
    }

    public function testBasicValidations()
    {
        $field = new FormField("test", 35);
        $field->validate([
            Rule::integer("Must be a valid integer.")
        ]);
        self::assertTrue($field->verify());
        self::assertFalse($field->hasError());
        self::assertEmpty($field->getErrorMessages());
        self::assertEmpty($field->getErrors());

        $field = new FormField("test", "wow");
        $field->validate([
            Rule::integer("Must be a valid integer.")
        ]);
        self::assertFalse($field->verify());
        self::assertTrue($field->hasError());
        var_dump($field->getErrorMessages());
        self::assertCount(1, $field->getErrorMessages());
        self::assertEquals("Must be a valid integer.", $field->getErrorMessages()[0]);
        self::assertEquals([
            'test' => ['Must be a valid integer.']
        ], $field->getErrors());
    }

    public function testEachIterationValidations()
    {
        $field = new FormField("fruits[]", ['apple', 'orange']);
        $field->validate([
            Rule::required("Fruit selection must not be empty."),
            Rule::array("Fruit must be a valid array."),
            Rule::each([
                Rule::inArray(['pineapple', 'grape', 'apple', 'orange', 'tomato'])
            ])
        ]);
        self::assertTrue($field->verify());

        $field = new FormField("fruits[]", ['apple', 'hot-dog']);
        $field->validate([
            Rule::required("Fruit selection must not be empty."),
            Rule::array("Fruit must be a valid array."),
            Rule::each([
                Rule::inArray(['pineapple', 'grape', 'apple', 'orange', 'tomato'], "The fruit [%s] is invalid.")
            ])
        ]);
        self::assertFalse($field->verify());
        self::assertEquals([
            'fruits[].1' => ['The fruit [hot-dog] is invalid.']
        ], $field->getErrors());
    }

    public function testEachIterationValidations2()
    {
        $field = new FormField("product_ids[]", [13, 209, 321, 'we', 50]);
        $field->validate([
            Rule::required("Product ids must be defined."),
            Rule::array("Product ids must be a valid array."),
            Rule::each([
                Rule::integer("Product id must be integer."),
                Rule::range(10, 300, "Product id must be between 10 and 300.")
            ])
        ]);
        self::assertFalse($field->verify());
        self::assertEquals([
            'product_ids[].2' => ['Product id must be between 10 and 300.'],
            'product_ids[].3' => ['Product id must be integer.']
        ], $field->getErrors());
    }

    public function testEachNested()
    {
        $field = new FormField("something[]", [
            ['bob', 'lewis'], ['bob', '#0A0A0A'], ['%?%?%?', 'lewis']
        ]);
        $field->validate([
            Rule::required("Something must be defined."),
            Rule::array("Something must be a valid array."),
            Rule::each([
                Rule::array("Inner something must be array."),
                Rule::each([
                    Rule::name("Something must be name.")
                ])
            ])
        ]);
        self::assertFalse($field->verify());
        self::assertEquals([
            'something[].1.1' => ['Something must be name.'],
            'something[].2.0' => ['Something must be name.']
        ], $field->getErrors());
    }

    public function testInvalidEachUsage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Data argument for the [each] rule must be an array. Consider adding a Rule::array beforehand.");
        $field = new FormField("product_ids[]", 13); // Not an array ...
        $field->validate([
            Rule::required("Product ids must be defined."),
            Rule::each([
                Rule::integer("Product id must be integer."),
                Rule::range(10, 300, "Product id must be between 10 and 300.")
            ])
        ]);
        $field->verify();
    }

    public function testInvalidEachUsage2()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Rules argument for [each] must be an array of Rule instances.");
        $field = new FormField("product_ids[]", [1, 2, 3]);
        $field->validate([
            Rule::required("Product ids must be defined."),
            Rule::array("Product ids must be a valid array."),
            Rule::each([
                Rule::integer("Product id must be integer."),
                'Bob Lewis' // Not a rule ...
            ])
        ]);
        $field->verify();
    }

    public function testEachKeyValidations()
    {
        $field = new FormField("fruits[]", [450 => 'apple', 500 => 'orange']);
        $field->validate([
            Rule::required("Fruit selection must not be empty."),
            Rule::array("Fruit must be a valid array."),
            Rule::eachKey([
                Rule::integer("Key must be integer."),
                Rule::range(400, 600, "Key must be between 400 and 600.")
            ]),
            Rule::each([
                Rule::inArray(['pineapple', 'grape', 'apple', 'orange', 'tomato'])
            ])
        ]);
        self::assertTrue($field->verify());

        $field = new FormField("fruits[]", [450 => 'apple', 'we' => 'orange', 900 => 'tomato', 460 => 'hot-dog']);
        $field->validate([
            Rule::required("Fruit selection must not be empty."),
            Rule::array("Fruit must be a valid array."),
            Rule::eachKey([
                Rule::integer("Key [%s] must be integer."),
                Rule::range(400, 600, "Key [%s] must be between 400 and 600.")
            ]),
            Rule::each([
                Rule::inArray(['pineapple', 'grape', 'apple', 'orange', 'tomato'], "The fruit [%s] is invalid.")
            ])
        ])->all();
        self::assertFalse($field->verify());
        self::assertEquals([
            'fruits[].we' => ['Key [we] must be integer.'],
            'fruits[].900' => ['Key [900] must be between 400 and 600.'],
            'fruits[].460' => ['The fruit [hot-dog] is invalid.']
        ], $field->getErrors());
    }

    public function testNestedArrayValidation()
    {
        $field = new FormField("student", [
            'name' => '',
            'age' => 17
        ]);
        $field->validate([
            Rule::nested("name", [
                Rule::required("Student name must not be empty.")
            ]),
            Rule::nested("age", [
                Rule::required("Student age must not be empty."),
                Rule::integer("Student age must be integer.")
            ])
        ])->all();
        self::assertFalse($field->verify());
        self::assertEquals([
            'student.name' => ['Student name must not be empty.']
        ], $field->getErrors());

        $field = new FormField("student", [
            'name' => '',
            'age' => 'we'
        ]);
        $field->validate([
            Rule::nested("name", [
                Rule::required("Student name must not be empty.")
            ]),
            Rule::nested("age", [
                Rule::required("Student age must not be empty."),
                Rule::integer("Student age must be integer.")
            ])
        ])->all();
        self::assertFalse($field->verify());
        self::assertEquals([
            'student.name' => ['Student name must not be empty.'],
            'student.age' => ['Student age must be integer.']
        ], $field->getErrors());
    }

    public function testNestedObjectValidation()
    {
        $field = new FormField("student", (object) [
            'name' => '',
            'age' => 17
        ]);
        $field->validate([
            Rule::nested("name", [
                Rule::required("Student name must not be empty.")
            ]),
            Rule::nested("age", [
                Rule::required("Student age must not be empty."),
                Rule::integer("Student age must be integer.")
            ])
        ])->all();
        self::assertFalse($field->verify());
        self::assertEquals([
            'student.name' => ['Student name must not be empty.']
        ], $field->getErrors());

        $field = new FormField("student", [
            'name' => '',
            'age' => 'we'
        ]);
        $field->validate([
            Rule::nested("name", [
                Rule::required("Student name must not be empty.")
            ]),
            Rule::nested("age", [
                Rule::required("Student age must not be empty."),
                Rule::integer("Student age must be integer.")
            ])
        ])->all();
        self::assertFalse($field->verify());
        self::assertEquals([
            'student.name' => ['Student name must not be empty.'],
            'student.age' => ['Student age must be integer.']
        ], $field->getErrors());
    }

    public function testNestedArrayRequiredValidation()
    {
        $field = new FormField("student", [
            'age' => 17
        ]);
        $field->validate([
            Rule::nested("name", [ // IS REQUIRED, but not set ... evaluates as null
                Rule::required("Student name must not be empty.")
            ]),
            Rule::nested("age", [
                Rule::required("Student age must not be empty."),
                Rule::integer("Student age must be integer.")
            ])
        ])->all();
        self::assertFalse($field->verify());
        self::assertEquals([
            'student.name' => ['Student name must not be empty.']
        ], $field->getErrors());
    }

    public function testNestedObjectRequiredValidation()
    {
        $field = new FormField("student", (object) [
            'age' => 17
        ]);
        $field->validate([
            Rule::nested("name", [ // IS REQUIRED, but not set ... evaluates as null
                Rule::required("Student name must not be empty.")
            ]),
            Rule::nested("age", [
                Rule::required("Student age must not be empty."),
                Rule::integer("Student age must be integer.")
            ])
        ])->all();
        self::assertFalse($field->verify());
        self::assertEquals([
            'student.name' => ['Student name must not be empty.']
        ], $field->getErrors());
    }

    public function testInvalidNestedUsage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Data argument for the [nested] rule must either be an associative array or an object. Consider adding a Rule::associativeArray or Rule::object beforehand.");
        $field = new FormField("student", 34);
        $field->validate([
            Rule::nested("name", [
                Rule::required("Student name must not be empty."),
                Rule::name("Student name is invalid.")
            ]),
            Rule::nested("age", [
                Rule::required("Student age must not be empty."),
                Rule::integer("Student age must be integer.")
            ]),
            Rule::nested("scoring", [
                Rule::associativeArray("Scoring must be associative array."),
                Rule::nested("lowest", [
                    Rule::integer("Low score [%s] invalid.")
                ]),
                Rule::nested("highest", [
                    Rule::integer("High score invalid.")
                ])
            ])
        ]);
        $field->verify();
    }

    public function testInvalidNestedUsage2()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Rules argument for [nested] must be an array of Rule instances.");
        $field = new FormField("student", [
            'name' => '',
            'age' => 17
        ]);
        $field->validate([
            Rule::nested("name", [
                Rule::required("Student name must not be empty.")
            ]),
            Rule::nested("age", [
                Rule::required("Student age must not be empty."),
                'TEST' // NOT A RULE ...
            ])
        ])->all();
        $field->verify();
    }

    public function testDeeperNestedValidation()
    {
        $field = new FormField("student", [
            'name' => 'Bob Lewis',
            'age' => 17,
            'scoring' => [
                'highest' => 90,
                'lowest' => "oops"
            ]
        ]);
        $field->validate([
            Rule::nested("name", [
                Rule::required("Student name must not be empty."),
                Rule::name("Student name is invalid.")
            ]),
            Rule::nested("age", [
                Rule::required("Student age must not be empty."),
                Rule::integer("Student age must be integer.")
            ]),
            Rule::nested("scoring", [
                Rule::associativeArray("Scoring must be associative array."),
                Rule::nested("lowest", [
                    Rule::integer("Low score [%s] invalid.")
                ]),
                Rule::nested("highest", [
                    Rule::integer("High score invalid.")
                ])
            ])
        ]);

        self::assertFalse($field->verify());
        self::assertEquals([
            'student.scoring.lowest' => ['Low score [oops] invalid.']
        ], $field->getErrors());
    }

    public function testEvenDeeperNestedValidation()
    {
        $field = new FormField("student", [
            'name' => 'Bob Lewis',
            'age' => 17,
            'scoring' => [
                'highest' => [
                    'first' => 96,
                    'second' => "Oups"
                ],
                'lowest' => 45
            ]
        ]);
        $field->validate([
            Rule::nested("name", [
                Rule::required("Student name must not be empty."),
                Rule::name("Student name is invalid.")
            ]),
            Rule::nested("age", [
                Rule::required("Student age must not be empty."),
                Rule::integer("Student age must be integer.")
            ]),
            Rule::nested("scoring", [
                Rule::associativeArray("Scoring must be associative array."),
                Rule::nested("lowest", [
                    Rule::integer("Low score [%s] invalid.")
                ]),
                Rule::nested("highest", [
                    Rule::associativeArray("Highest must be array."),
                    Rule::nested("first", [
                        Rule::required("First high score required."),
                        Rule::integer("First high score must be integer.")
                    ]),
                    Rule::nested("second", [
                        Rule::required("Second high score required."),
                        Rule::integer("Second high score must be integer.")
                    ]),
                    Rule::nested("third", [
                        Rule::required("Third high score required."),
                        Rule::integer("Third high score must be integer.")
                    ])
                ])
            ])
        ]);

        self::assertFalse($field->verify());
        self::assertEquals([
            'student.scoring.highest.second' => ['Second high score must be integer.'],
            'student.scoring.highest.third' => ['Third high score required.']
        ], $field->getErrors());
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
        $field->validate([
            Rule::nested("scoring", [
                Rule::associativeArray("Scoring must be associative array."),
                Rule::nested('highest', [
                    Rule::array("Highest scores must be array."),
                    Rule::each([Rule::integer("Score invalid must be integer.")])
                ]),
                Rule::nested('lowest', [
                    Rule::integer("Lowest score must be integer.")
                ])
            ])
        ]);
        self::assertFalse($field->verify());
        self::assertEquals("student.scoring.highest.1", array_keys($field->getErrors())[0]);
        self::assertEquals("Score invalid must be integer.", $field->getErrorMessages()[0]);
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
        $field->validate([
            Rule::nested("classes", [
                Rule::array("Classes must be array of object."),
                Rule::each([
                    Rule::object("Classes must be object."),
                    Rule::nested('title', [
                        Rule::required("Title must not be empty."),
                        Rule::name("Title is invalid.")
                    ])
                ])
            ]),
            Rule::nested("scoring", [
                Rule::associativeArray("Scoring must be associative array."),
                Rule::nested('highest', [
                    Rule::array("Highest scores must be array."),
                    Rule::each([Rule::integer("Score invalid must be integer.")])
                ]),
                Rule::nested('lowest', [
                    Rule::integer("Lowest score must be integer.")
                ])
            ])
        ]);
        self::assertFalse($field->verify());
        self::assertEquals([
            'student.classes.1.title' => ['Title must not be empty.']
        ], $field->getErrors());
    }

    public function testDeeperIndexMultipleAllNestedErrorPathing()
    {
        $field = new FormField("student", [
            'name' => 'Bob Lewis',
            'age' => 17,
            'classes' => [
                (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => 3],
                (object) ['title' => '', 'description' => 'Lorem', 'hours' => 3],
                (object) ['title' => 'Testing', 'description' => 'Lorem', 'hours' => 'we']
            ],
            'scoring' => [
                'highest' => [96, 90, 85],
                'lowest' => 45
            ]
        ]);
        $field->validate([
            Rule::nested("classes", [
                Rule::array("Classes must be array of object."),
                Rule::each([
                    Rule::object("Classes must be object."),
                    Rule::nested('title', [
                        Rule::required("Title must not be empty."),
                        Rule::name("Title is invalid.")
                    ]),
                    Rule::nested('description', [
                        Rule::required("Description must not be empty.")
                    ]),
                    Rule::nested('hours', [
                        Rule::required("Hours must not be empty."),
                        Rule::integer("Hours [%s] must be integer.")
                    ])
                ])
            ]),
            Rule::nested("scoring", [
                Rule::associativeArray("Scoring must be associative array."),
                Rule::nested('highest', [
                    Rule::array("Highest scores must be array."),
                    Rule::each([Rule::integer("Score invalid must be integer.")])
                ]),
                Rule::nested('lowest', [
                    Rule::integer("Lowest score must be integer.")
                ])
            ])
        ]);

        self::assertFalse($field->verify());
        self::assertEquals([
            'student.classes.1.title' => ['Title must not be empty.'],
            'student.classes.2.hours' => ['Hours [we] must be integer.']
        ], $field->getErrors());
    }
}
