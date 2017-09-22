<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\Validations\BaseValidation;

class BaseValidationTest extends TestCase
{
    public function testIsNotEmpty()
    {
        self::assertTrue(BaseValidation::isNotEmpty('testing'));
        self::assertFalse(BaseValidation::isNotEmpty(''));
    }

    public function testIsAlpha()
    {
        self::assertTrue(BaseValidation::isAlpha('test'));
        self::assertTrue(BaseValidation::isAlpha('marc-antoine'));
        self::assertTrue(BaseValidation::isAlpha('Émilie'));
        self::assertFalse(BaseValidation::isAlpha('bob129'));
        self::assertFalse(BaseValidation::isAlpha('dhhtgerg&@esjhgdkg'));
    }

    public function testIsAlphanumeric()
    {
        self::assertTrue(BaseValidation::isAlphanumeric('test'));
        self::assertTrue(BaseValidation::isAlphanumeric('test1234'));
        self::assertFalse(BaseValidation::isAlphanumeric('test+test'));
        self::assertFalse(BaseValidation::isAlphanumeric('@bob'));
    }

    public function testIsPasswordCompliant()
    {
        self::assertTrue(BaseValidation::isPasswordCompliant('Omega12345'));
        self::assertFalse(BaseValidation::isPasswordCompliant('password'));
        self::assertFalse(BaseValidation::isPasswordCompliant('1234'));
        self::assertFalse(BaseValidation::isPasswordCompliant('test12345'));
    }

    public function testIsDate()
    {
        self::assertTrue(BaseValidation::isDate('2016-01-01'));
        self::assertFalse(BaseValidation::isDate('-109-01-01'));
        self::assertFalse(BaseValidation::isDate('2016-31-31'));
        self::assertFalse(BaseValidation::isDate('2016-02-30'));
        self::assertFalse(BaseValidation::isDate('2016'));
    }

    public function testIsEmail()
    {
        self::assertTrue(BaseValidation::isEmail('davidt2003@msn.com'));
        self::assertTrue(BaseValidation::isEmail('bob@lewis.a'));
        self::assertFalse(BaseValidation::isEmail('boblewis'));
        self::assertFalse(BaseValidation::isEmail('bob@lewis'));
        self::assertFalse(BaseValidation::isEmail('bob.com'));
    }

    public function testIsPhone()
    {
        self::assertTrue(BaseValidation::isPhone('450-555-5555'));
        self::assertTrue(BaseValidation::isPhone('(450) 555-5555'));
        self::assertTrue(BaseValidation::isPhone('1-450-555-5555'));
        self::assertTrue(BaseValidation::isPhone('1 (450) 555-5555'));
        self::assertFalse(BaseValidation::isPhone('450-eee-3422'));
        self::assertFalse(BaseValidation::isPhone(''));
    }

    public function testIsUrl()
    {
        self::assertTrue(BaseValidation::isUrl('www.bob.com'));
        self::assertTrue(BaseValidation::isUrl('http://www.bob.com'));
        self::assertTrue(BaseValidation::isUrl('https://www.bob.com'));
        self::assertTrue(BaseValidation::isUrl('www.bob.ca'));
        self::assertTrue(BaseValidation::isUrl('www.bob.ca:80'));
        self::assertFalse(BaseValidation::isUrl('wsdghfggfdgh'));
        self::assertFalse(BaseValidation::isUrl(''));
        self::assertFalse(BaseValidation::isUrl('bob.com'));
    }

    public function testIsYouTubeUrl()
    {
        self::assertTrue(BaseValidation::isYoutubeUrl('www.youtube.com/watch?v=DFYRQ_zQ-gk'));
        self::assertTrue(BaseValidation::isYoutubeUrl('http://www.youtube.com/watch?v=DFYRQ_zQ-gk'));
        self::assertTrue(BaseValidation::isYoutubeUrl('https://www.youtube.com/watch?v=DFYRQ_zQ-gk'));
        self::assertTrue(BaseValidation::isYoutubeUrl('m.youtube.com/watch?v=DFYRQ_zQ-gk'));
        self::assertTrue(BaseValidation::isYoutubeUrl('youtube.com/v/DFYRQ_zQ-gk?fs=1&hl=en_US'));
        self::assertTrue(BaseValidation::isYoutubeUrl('https://www.youtube.com/embed/DFYRQ_zQ-gk?autoplay=1'));
        self::assertTrue(BaseValidation::isYoutubeUrl('https://youtu.be/DFYRQ_zQ-gk?t=120'));
        self::assertTrue(BaseValidation::isYoutubeUrl('youtu.be/DFYRQ_zQ-gk'));
        self::assertFalse(BaseValidation::isYoutubeUrl('youtu.yu/DFYRQ_zQ-gk'));
        self::assertFalse(BaseValidation::isYoutubeUrl('www.youtobe.com/watch?v=DFYRQ_zQ-gk'));
    }
}