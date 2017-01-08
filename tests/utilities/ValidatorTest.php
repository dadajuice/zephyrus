<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\Validator;

class ValidatorTest extends TestCase
{
    public function testIsNotEmpty()
    {
        self::assertTrue(Validator::isNotEmpty('testing'));
        self::assertFalse(Validator::isNotEmpty(''));
    }

    public function testIsDecimal()
    {
        self::assertTrue(Validator::isDecimal('10.45657'));
        self::assertTrue(Validator::isDecimal('10,45657'));
        self::assertTrue(Validator::isDecimal('10'));
        self::assertFalse(Validator::isDecimal('ert'));
        self::assertFalse(Validator::isDecimal('0ert'));
        self::assertFalse(Validator::isDecimal('ert0'));
        self::assertFalse(Validator::isDecimal('.'));
        self::assertFalse(Validator::isDecimal('0.'));
        self::assertFalse(Validator::isDecimal('.0'));
        self::assertFalse(Validator::isDecimal('-1'));
    }

    public function testIsInteger()
    {
        self::assertTrue(Validator::isInteger('10'));
        self::assertFalse(Validator::isInteger('10.34'));
        self::assertFalse(Validator::isInteger('-10'));
        self::assertFalse(Validator::isInteger('er'));
    }

    public function testIsSignedDecimal()
    {
        self::assertTrue(Validator::isSignedDecimal('10.45657'));
        self::assertTrue(Validator::isSignedDecimal('10,45657'));
        self::assertTrue(Validator::isSignedDecimal('10'));
        self::assertFalse(Validator::isSignedDecimal('ert'));
        self::assertFalse(Validator::isSignedDecimal('0ert'));
        self::assertFalse(Validator::isSignedDecimal('ert0'));
        self::assertFalse(Validator::isSignedDecimal('.'));
        self::assertFalse(Validator::isSignedDecimal('0.'));
        self::assertFalse(Validator::isSignedDecimal('.0'));
        self::assertTrue(Validator::isSignedDecimal('-1'));
        self::assertTrue(Validator::isSignedDecimal('-1.0'));
        self::assertTrue(Validator::isSignedDecimal('-1,34'));
        self::assertTrue(Validator::isSignedDecimal('-1234,12345'));
    }

    public function testIsSignedInteger()
    {
        self::assertTrue(Validator::isSignedInteger('10'));
        self::assertTrue(Validator::isSignedInteger('-10'));
        self::assertFalse(Validator::isSignedInteger('-10,34'));
        self::assertFalse(Validator::isSignedInteger('er'));
    }

    public function testIsAlphanumeric()
    {
        self::assertTrue(Validator::isAlphanumeric('test'));
        self::assertTrue(Validator::isAlphanumeric('test1234'));
        self::assertFalse(Validator::isAlphanumeric('test+test'));
        self::assertFalse(Validator::isAlphanumeric('@bob'));
    }

    public function testIsPasswordCompliant()
    {
        self::assertTrue(Validator::isPasswordCompliant('Omega12345'));
        self::assertFalse(Validator::isPasswordCompliant('password'));
        self::assertFalse(Validator::isPasswordCompliant('1234'));
        self::assertFalse(Validator::isPasswordCompliant('test12345'));
    }

    public function testIsDate()
    {
        self::assertTrue(Validator::isDate('2016-01-01'));
        self::assertFalse(Validator::isDate('-109-01-01'));
        self::assertFalse(Validator::isDate('2016-31-31'));
        self::assertFalse(Validator::isDate('2016-02-30'));
        self::assertFalse(Validator::isDate('2016'));
    }

    public function testIsEmail()
    {
        self::assertTrue(Validator::isEmail('davidt2003@msn.com'));
        self::assertTrue(Validator::isEmail('bob@lewis.a'));
        self::assertFalse(Validator::isEmail('boblewis'));
        self::assertFalse(Validator::isEmail('bob@lewis'));
        self::assertFalse(Validator::isEmail('bob.com'));
    }

    public function testIsPhone()
    {
        self::assertTrue(Validator::isPhone('450-555-5555'));
        self::assertTrue(Validator::isPhone('(450) 555-5555'));
        self::assertTrue(Validator::isPhone('1-450-555-5555'));
        self::assertTrue(Validator::isPhone('1 (450) 555-5555'));
        self::assertFalse(Validator::isPhone('450-eee-3422'));
        self::assertFalse(Validator::isPhone(''));
    }

    public function testIsUrl()
    {
        self::assertTrue(Validator::isUrl('www.bob.com'));
        self::assertTrue(Validator::isUrl('http://www.bob.com'));
        self::assertTrue(Validator::isUrl('https://www.bob.com'));
        self::assertTrue(Validator::isUrl('www.bob.ca'));
        self::assertTrue(Validator::isUrl('www.bob.ca:80'));
        self::assertFalse(Validator::isUrl('wsdghfggfdgh'));
        self::assertFalse(Validator::isUrl(''));
        self::assertFalse(Validator::isUrl('bob.com'));
    }

    public function testIsYouTubeUrl()
    {
        self::assertTrue(Validator::isYoutubeUrl('www.youtube.com/watch?v=DFYRQ_zQ-gk'));
        self::assertTrue(Validator::isYoutubeUrl('http://www.youtube.com/watch?v=DFYRQ_zQ-gk'));
        self::assertTrue(Validator::isYoutubeUrl('https://www.youtube.com/watch?v=DFYRQ_zQ-gk'));
        self::assertTrue(Validator::isYoutubeUrl('m.youtube.com/watch?v=DFYRQ_zQ-gk'));
        self::assertTrue(Validator::isYoutubeUrl('youtube.com/v/DFYRQ_zQ-gk?fs=1&hl=en_US'));
        self::assertTrue(Validator::isYoutubeUrl('https://www.youtube.com/embed/DFYRQ_zQ-gk?autoplay=1'));
        self::assertTrue(Validator::isYoutubeUrl('https://youtu.be/DFYRQ_zQ-gk?t=120'));
        self::assertTrue(Validator::isYoutubeUrl('youtu.be/DFYRQ_zQ-gk'));
        self::assertFalse(Validator::isYoutubeUrl('youtu.yu/DFYRQ_zQ-gk'));
        self::assertFalse(Validator::isYoutubeUrl('www.youtobe.com/watch?v=DFYRQ_zQ-gk'));
    }
}