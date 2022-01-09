<?php namespace Zephyrus\Tests\Application\Form;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Rule;

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
}
