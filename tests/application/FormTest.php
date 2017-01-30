<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Form;
use Zephyrus\Utilities\Validator;

class FormTest extends TestCase
{
    public function testErrors()
    {
        $form = new Form();
        $form->addField('name', '');
        $form->addField('price', '12.50e');
        $form->addRule('name', Validator::NOT_EMPTY, 'err_1');
        $form->addRule('name', Validator::ALPHANUMERIC, 'err_2', Form::TRIGGER_FIELD_NO_ERROR);
        $form->addRule('price', Validator::NOT_EMPTY, 'err_3');
        $form->addRule('price', Validator::DECIMAL, 'err_4');
        $form->verify();
        $errors = $form->getErrorMessages();
        self::assertEquals('err_1', $errors[0]);
        self::assertEquals('err_4', $errors[1]);
    }

    public function testAddFields()
    {
        $form = new Form();
        $form->addFields(['name' => 'bob', 'price' => '10.00']);
        self::assertEquals('bob', $form->getValue('name'));
        $fields = $form->getFields();
        self::assertEquals('bob', $fields['name']);
    }

    public function testRemoveAllMemorized()
    {
        $_SESSION['_FIELDS'] = [
            'name' => 'bob',
            'price' => '12.5'
        ];
        Form::removeMemorizedValue();
        self::assertFalse(isset($_SESSION['_FIELDS']));
    }

    public function testBuildObject()
    {
        $form = new Form();
        $form->addFields(['name' => 'bob', 'price' => '10.00']);
        $class = new class() {
            private $name;
            private $price;

            public function getName()
            {
                return $this->name;
            }

            public function setName($name)
            {
                $this->name = $name;
            }

            public function getPrice()
            {
                return $this->price;
            }

            public function setPrice($price)
            {
                $this->price = $price;
            }
        };
        $form->buildObject($class);
        self::assertEquals('bob', $class->getName());
        self::assertEquals('10.00', $class->getPrice());
    }

    public function testAddErrors()
    {
        $form = new Form();
        $form->addError('name', 'err_1');
        $errors = $form->getErrors();
        self::assertEquals('err_1', $errors['name'][0]);
    }

    public function testMemorization()
    {
        $form = new Form();
        $form->addField('name', 'bob');
        $form->addRule('name', Validator::NOT_EMPTY, 'err_1');
        $form->verify();
        self::assertEquals('bob', Form::readMemorizedValue('name'));
        self::assertEquals('lewis', Form::readMemorizedValue('gfdfg', 'lewis'));
    }


    public function testErrorTrigger()
    {
        $form = new Form();
        $form->addField('name', 'bob');
        $form->addField('price', '12.50e');
        $form->addRule('name', Validator::NOT_EMPTY, 'err_1');
        $form->addRule('name', Validator::ALPHANUMERIC, 'err_2', Form::TRIGGER_FIELD_NO_ERROR);
        $form->addRule('price', Validator::NOT_EMPTY, 'err_3');
        $form->addRule('price', Validator::DECIMAL, 'err_4', Form::TRIGGER_NO_ERROR);
        $form->verify();
        $errors = $form->getErrorMessages();
        self::assertEquals('err_4', $errors[0]);
    }

    public function testValidationFunction()
    {
        $form = new Form();
        $form->addField('name', 'bob');
        $form->addField('price', '8.00');
        $form->addRule('price', function($value) {
            return $value > 10;
        }, 'err_1');
        $form->verify();
        $errors = $form->getErrorMessages();
        self::assertEquals('err_1', $errors[0]);
    }

    public function testValidationCallback()
    {
        $form = new Form();
        $form->addField('name', 'bob');
        $form->addField('price', '12.00');
        $form->addRule('price', function($value, $data) {
            return $value > 10 && $data['name'] == 'bob';
        }, 'err_1');
        $result = $form->verify();
        self::assertTrue($result);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidRuleField()
    {
        $form = new Form();
        $form->addField('name', '');
        $form->addRule('bob', Validator::ALPHANUMERIC, "err_1");
    }
}