<?php namespace Zephyrus\Tests\Application\Form;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Form;
use Zephyrus\Application\Rule;
use Zephyrus\Application\Session;

class FormMemorizationTest extends TestCase
{
    public function testMemorization()
    {
        Session::getInstance()->start();
        Form::removeMemorizedValue();
        $form = new Form();
        $form->addField('name', 'bob');
        $form->addField('age', '');
        $form->field('name')->validate(Rule::notEmpty('err_1'));
        $form->field('age')->validate(Rule::notEmpty('err_2')); // Will not be registered
        $form->verify();

        self::assertEquals('bob', Form::readMemorizedValue('name'));
        self::assertEquals('bob', val('name'));
        self::assertEquals('lewis', Form::readMemorizedValue('age', 'lewis'));
        self::assertEquals('lewis', val('age', 'lewis'));

        // Back to no initiated session
        Session::getInstance()->destroy();
        Session::kill();
    }

    public function testMemorizationWithoutSession()
    {
        Session::getInstance()->start();
        Form::removeMemorizedValue();
        $form = new Form();
        $form->addField('name', 'bob');
        $form->field('name')->validate(Rule::notEmpty('err_1'));
        $form->verify();

        // When session dies, fields are no longer registered ...
        Session::getInstance()->destroy();
        Session::kill();

        self::assertEquals('failed', Form::readMemorizedValue('name', 'failed'));
    }

    public function testRemoveAllMemorized()
    {
        Session::getInstance()->start();
        $_SESSION['_FIELDS'] = [
            'name' => 'bob',
            'price' => '12.5'
        ];
        Form::removeMemorizedValue();
        self::assertFalse(isset($_SESSION['_FIELDS']));

        // Back to no initiated session
        Session::getInstance()->destroy();
        Session::kill();
    }
}
