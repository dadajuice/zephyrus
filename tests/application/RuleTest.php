<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validator;

class RuleTest extends TestCase
{
    public function testIsValid()
    {
        $rule = new Rule(function($value) {
            return $value == 'allo';
        }, "failed");
        self::assertTrue($rule->isValid('allo'));
        self::assertEquals('failed', $rule->getErrorMessage());
    }

    public function testIsValidOptionalMessage()
    {
        $rule = new Rule(Validator::EMAIL);
        $rule->setErrorMessage("failed");
        self::assertTrue($rule->isValid('allo@bob.com'));
        self::assertEquals('failed', $rule->getErrorMessage());
    }
}
