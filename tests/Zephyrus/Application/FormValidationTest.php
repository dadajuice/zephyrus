<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Form;
use Zephyrus\Application\Rule;

class FormValidationTest extends TestCase
{
    public function testSimpleFieldValidation()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'blewis'
        ]);
        $form->field('username')->validate([Rule::notEmpty('username must not be empty')]);
        self::assertTrue($form->verify());
        self::assertFalse($form->hasError());
    }

    public function testFailedSimpleFieldValidation()
    {
        $form = new Form();
        $form->addFields([
            'username' => ''
        ]);
        $form->field('username')->validate([Rule::notEmpty('username must not be empty')]);
        self::assertFalse($form->verify());
        self::assertTrue($form->hasError());
        self::assertEquals("username must not be empty", $form->getErrorMessages()[0]);
    }

    public function testChainFieldValidation()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'blewis'
        ]);
        $form->field('username')
            ->validate([Rule::notEmpty('username must not be empty')])
            ->validate([new Rule(function ($value) {
                return $value != "admin";
            }, "username must not be admin")]);
        self::assertTrue($form->verify());
        self::assertFalse($form->hasError());
    }

    public function testFailedChainFieldValidation()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'admin'
        ]);
        $form->field('username')
            ->validate([Rule::notEmpty('username must not be empty')])
            ->validate([new Rule(function ($value) {
                return $value != "admin";
            }, "username must not be admin")]);
        self::assertFalse($form->verify());
        self::assertEquals("username must not be admin", $form->getErrorMessages()[0]);
    }

    public function testMultipleChainFieldValidation()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'blewis',
            'age' => 19
        ]);
        $form->field('username')
            ->validate([Rule::notEmpty('username must not be empty')])
            ->validate([new Rule(function ($value) {
                return $value != "admin";
            }, "username must not be admin")]);
        $form->field("age")
            ->validate([Rule::integer("age must be int")])
            ->validate([Rule::range(18, 99, "age must be between 18 and 99 years old")]);
        self::assertTrue($form->verify());
    }

    public function testFailedMultipleChainFieldValidation()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'admin',
            'age' => 13
        ]);
        $form->field('username')
            ->validate([Rule::notEmpty('username must not be empty')])
            ->validate([new Rule(function ($value) {
                return $value != "admin";
            }, "username must not be admin")]);
        $form->field("age")
            ->validate([Rule::integer("age must be int")])
            ->validate([Rule::range(18, 99, "age must be between 18 and 99 years old")]);
        self::assertFalse($form->verify());
        self::assertCount(2, $form->getErrorMessages());
        self::assertEquals("username must not be admin", $form->getErrorMessages()[0]);
        self::assertEquals("age must be between 18 and 99 years old", $form->getErrorMessages()[1]);
    }

    public function testGroupedFieldValidation()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'blewis',
            'firstname' => 'bob',
            'lastname' => 'lewis',
            'password' => '123'
        ]);
        $form->field('username')->validate([
            Rule::notEmpty("username must not be empty"),
            Rule::name("username is invalid")
        ]);
        $form->field('password')->validate([
            Rule::notEmpty("password must not be empty"),
            Rule::passwordCompliant("password is not compliant")
        ]);
        self::assertFalse($form->verify());
        self::assertEquals("password is not compliant", $form->getErrorMessages()[0]);
    }

    public function testAllFieldValidation()
    {
        $form = new Form();
        $form->addField('something', 'Martin Sandwish');
        $form->addField('age', 'lul');
        $form->field("something")
            ->validate([Rule::integer('err_1')])
            ->validate([Rule::ipAddress('err_2')])
            ->all();
        $form->field("age")->validate([Rule::integer('err_3')]);
        $form->verify();
        $errors = $form->getErrorMessages();
        self::assertEquals('err_1', $errors[0]);
        self::assertEquals('err_2', $errors[1]);
        self::assertEquals('err_3', $errors[2]);
    }

    public function testRuleAllField()
    {
        $form = new Form();
        $form->addField('ids[]', [1, 2, 3, 4, 5, 6]);
        $form->field("ids[]")->validate([Rule::each([Rule::integer()])]);
        self::assertTrue($form->verify());
    }

    public function testRuleAllField2()
    {
        $form = new Form();
        $form->addField('test', 'bob');
        $form->addField('names[]', ['bob', 'bob', 'bob']);
        $form->field('names[]', [Rule::each([Rule::sameAs('test')])]);
        self::assertTrue($form->verify());
    }

    public function testInvalidRuleAllField()
    {
        $form = new Form();
        $form->addField('ids[]', [1, 2, 3, "err", 5, 6]);
        $form->field("ids[]")
            ->validate([Rule::each([Rule::integer("err_99")])]);
        self::assertFalse($form->verify());
        self::assertEquals('err_99', $form->getErrorMessages()[0]);
    }

    public function testOptionalFieldValidation()
    {
        $form = new Form();
        $form->addFields([
            'email' => '', // Email is set but empty
            'username' => ''
        ]);
        $form->field("email")->validate([Rule::email("err_1")]);
        self::assertFalse($form->verify());
    }

    public function testOptionalFieldValidation2()
    {
        $form = new Form();
        $form->addFields([
            'email' => '',
            'username' => ''
        ]);
        $form->field("email", [Rule::email("err_1")])->optional();
        self::assertTrue($form->verify());
    }

    public function testOptionalFieldValidation3()
    {
        $form = new Form();
        $form->addFields([ // email not even registered! But is optional.
            'username' => ''
        ]);
        $form->field("email")
            ->validate([Rule::email("err_1")])->optional();
        self::assertTrue($form->verify());
    }
}
