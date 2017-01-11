<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\Validations\NumericValidation;

class NumericValidationTest extends TestCase
{
    public function testIsDecimal()
    {
        self::assertTrue(NumericValidation::isDecimal('10.45657'));
        self::assertTrue(NumericValidation::isDecimal('10,45657'));
        self::assertTrue(NumericValidation::isDecimal('10'));
        self::assertFalse(NumericValidation::isDecimal('ert'));
        self::assertFalse(NumericValidation::isDecimal('0ert'));
        self::assertFalse(NumericValidation::isDecimal('ert0'));
        self::assertFalse(NumericValidation::isDecimal('.'));
        self::assertFalse(NumericValidation::isDecimal('0.'));
        self::assertFalse(NumericValidation::isDecimal('.0'));
        self::assertFalse(NumericValidation::isDecimal('-1'));
    }

    public function testIsInteger()
    {
        self::assertTrue(NumericValidation::isInteger('10'));
        self::assertFalse(NumericValidation::isInteger('10.34'));
        self::assertFalse(NumericValidation::isInteger('-10'));
        self::assertFalse(NumericValidation::isInteger('er'));
    }

    public function testIsSignedDecimal()
    {
        self::assertTrue(NumericValidation::isSignedDecimal('10.45657'));
        self::assertTrue(NumericValidation::isSignedDecimal('10,45657'));
        self::assertTrue(NumericValidation::isSignedDecimal('10'));
        self::assertFalse(NumericValidation::isSignedDecimal('ert'));
        self::assertFalse(NumericValidation::isSignedDecimal('0ert'));
        self::assertFalse(NumericValidation::isSignedDecimal('ert0'));
        self::assertFalse(NumericValidation::isSignedDecimal('.'));
        self::assertFalse(NumericValidation::isSignedDecimal('0.'));
        self::assertFalse(NumericValidation::isSignedDecimal('.0'));
        self::assertTrue(NumericValidation::isSignedDecimal('-1'));
        self::assertTrue(NumericValidation::isSignedDecimal('-1.0'));
        self::assertTrue(NumericValidation::isSignedDecimal('-1,34'));
        self::assertTrue(NumericValidation::isSignedDecimal('-1234,12345'));
    }

    public function testIsSignedInteger()
    {
        self::assertTrue(NumericValidation::isSignedInteger('10'));
        self::assertTrue(NumericValidation::isSignedInteger('-10'));
        self::assertFalse(NumericValidation::isSignedInteger('-10,34'));
        self::assertFalse(NumericValidation::isSignedInteger('er'));
    }
}