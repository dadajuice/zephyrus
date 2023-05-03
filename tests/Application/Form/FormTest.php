<?php namespace Zephyrus\Tests\Application\Form;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Feedback;
use Zephyrus\Application\Form;
use Zephyrus\Application\Rule;
use Zephyrus\Application\Session;

class FormTest extends TestCase
{
    public function testUnregistered()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'blewis'
        ]);
        // simulate unchecked checkbox
        self::assertTrue($form->isRegistered('username'));
        self::assertFalse($form->isRegistered('understand'));
        $form->field('understand')->validate(Rule::notEmpty('you need to understand'));
        self::assertTrue($form->isRegistered('understand'));
        $form->verify();
        self::assertEquals('you need to understand', $form->getErrors()['understand'][0]);
        self::assertFalse($form->hasError('username'));
        self::assertTrue($form->hasError('understand'));
        self::assertTrue($form->hasError());
    }

    public function testSimpleErrorMessages()
    {
        $form = new Form();
        $form->addFields([
            'username' => ''
        ]);
        $form->field('username')
            ->validate(Rule::notEmpty('username not empty'));

        self::assertFalse($form->verify());
        self::assertCount(1, $form->getErrorMessages());
        self::assertTrue(key_exists('username', $form->getErrors()));
        self::assertEquals('username not empty', $form->getErrors()['username'][0]);
    }

    public function testManualSimpleErrorMessages()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'blewis'
        ]);
        $form->field('username')->validate(Rule::notEmpty('username not empty'));
        $form->addError('name', 'name must not be empty');
        self::assertFalse($form->verify()); // The defined validation pass, but there has been a manual error entered
        self::assertTrue(key_exists('name', $form->getErrors()));
        self::assertEquals('name must not be empty', $form->getErrors()['name'][0]);
    }

    public function testCombinedErrorMessages()
    {
        $form = new Form();
        $form->addFields([
            'username' => ''
        ]);
        $form->field('username')
            ->validate(Rule::notEmpty('username not empty'));
        $form->addError('name', 'err-1');
        self::assertFalse($form->verify());
        self::assertCount(2, $form->getErrorMessages());
        self::assertTrue(key_exists('username', $form->getErrors()));
        self::assertTrue(key_exists('name', $form->getErrors()));
        self::assertEquals('username not empty', $form->getErrors()['username'][0]);
        self::assertEquals('err-1', $form->getErrors()['name'][0]);
    }

    public function testFeedback()
    {
        Session::getInstance()->start();
        $form = new Form();
        $form->addFields([
            'username' => ''
        ]);
        $form->field('username')->validate(Rule::notEmpty('username not empty'));
        self::assertFalse($form->verify());
        $form->registerFeedback();
        $feedback = Feedback::readAll()->error;
        self::assertTrue(key_exists('username', $feedback));
        self::assertEquals('username not empty', $feedback['username'][0]);
        Session::kill();
    }

    public function testReadDefinedValues()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'blewis',
            'firstname' => 'bob'
        ]);
        self::assertEquals('blewis', $form->getValue('username'));
        self::assertEquals('bob', $form->getFields()['firstname']);
    }

    public function testReadDefaultValue()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'blewis',
            'firstname' => 'bob'
        ]);
        self::assertEquals('my_default', $form->getValue('test', 'my_default'));
    }

    public function testSimpleCustomRule()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'admin'
        ]);

        // Custom rule, username must not be admin!
        $customRule = new Rule(function ($value) {
            return $value != 'admin';
        }, 'username not valid');
        $form->field('username')->validate($customRule);
        self::assertFalse($form->verify());
        self::assertEquals('username not valid', $form->getErrorMessages()[0]);
    }

    public function testSimpleCustomRule2()
    {
        $form = new Form();
        $form->addFields([
            'password' => 'omega123',
            'password-confirm' => 'omega'
        ]);

        // Custom rule using all form fields
        $form->field('password')->validate(new Rule(function ($value, $fields) {
            return $value == $fields['password-confirm']; // Note that this specific case can use the Rule::sameAs
        }, 'password not valid'));
        self::assertFalse($form->verify());
        self::assertEquals('password not valid', $form->getErrorMessages()[0]);
    }

    public function testRemoveField()
    {
        $form = new Form();
        $form->addField('name', 'oui');
        $form->addField('name2', 'bob2');
        self::assertTrue($form->isRegistered('name2'));
        $form->removeField('name2');
        self::assertFalse($form->isRegistered('name2'));
    }

    public function testBuildStdClass()
    {
        $form = new Form();
        $form->addFields(['name' => 'bob', 'price' => '10.00']);
        $object = $form->buildObject();
        self::assertEquals('bob', $object->name);
        self::assertEquals('10.00', $object->price);
    }

    /**
     * Shall be removed once the setOptionalOnEmpty method is removed.
     *
     * @deprecated
     */
    public function testDeprecatedOptionalFieldMode()
    {
        $form = new Form();
        $form->setOptionalOnEmpty(false);
        self::assertFalse($form->isOptionalOnEmpty());
        $form->addFields([
            'email' => ''
        ]);
        $form->validate('email', Rule::email('email not valid'), true);
        self::assertFalse($form->verify());
    }
}
