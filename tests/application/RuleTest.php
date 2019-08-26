<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validations\ValidationCallback;

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
        $rule = new Rule(ValidationCallback::EMAIL);
        $rule->setErrorMessage("failed");
        self::assertTrue($rule->isValid('allo@bob.com'));
        self::assertEquals('failed', $rule->getErrorMessage());
    }

    public function testIsPasswordCompliant()
    {
        $rule = Rule::passwordCompliant();
        self::assertTrue($rule->isValid('Omega1234'));
        self::assertFalse($rule->isValid('password'));
        self::assertFalse($rule->isValid('1234'));
        self::assertFalse($rule->isValid('test12345'));
    }

    public function testIsDecimal()
    {
        $rule = Rule::decimal();
        self::assertTrue($rule->isValid(1.2));
        self::assertFalse($rule->isValid(-2.5));
        self::assertTrue($rule->isValid('10.45657'));
        self::assertTrue($rule->isValid('10,45657'));
        self::assertTrue($rule->isValid('10'));
        self::assertFalse($rule->isValid('ert'));
        self::assertFalse($rule->isValid('0ert'));
        self::assertFalse($rule->isValid('ert0'));
        self::assertFalse($rule->isValid('.'));
        self::assertFalse($rule->isValid('0.'));
        self::assertFalse($rule->isValid('.0'));
        self::assertFalse($rule->isValid('-1'));
    }

    public function testIsInteger()
    {
        $rule = Rule::integer();
        self::assertTrue($rule->isValid('10'));
        self::assertFalse($rule->isValid('10.34'));
        self::assertFalse($rule->isValid('-10'));
        self::assertFalse($rule->isValid('er'));
    }

    public function testIsSignedDecimal()
    {
        $rule = Rule::decimal("err", true);
        self::assertTrue($rule->isValid('10.45657'));
        self::assertTrue($rule->isValid('10,45657'));
        self::assertTrue($rule->isValid('10'));
        self::assertFalse($rule->isValid('ert'));
        self::assertFalse($rule->isValid('0ert'));
        self::assertFalse($rule->isValid('ert0'));
        self::assertFalse($rule->isValid('.'));
        self::assertFalse($rule->isValid('0.'));
        self::assertFalse($rule->isValid('.0'));
        self::assertTrue($rule->isValid('-1'));
        self::assertTrue($rule->isValid('-1.0'));
        self::assertTrue($rule->isValid('-1,34'));
        self::assertTrue($rule->isValid('-1234,12345'));
    }

    public function testIsSignedInteger()
    {
        $rule = Rule::integer("", true);
        self::assertTrue($rule->isValid('10'));
        self::assertTrue($rule->isValid('-10'));
        self::assertFalse($rule->isValid('-10,34'));
        self::assertFalse($rule->isValid('er'));
    }

    public function testIsEmail()
    {
        $rule = Rule::email();
        self::assertTrue($rule->isValid("bob@lewis.com"));
        self::assertTrue($rule->isValid("davidt2003@msn.com"));
        self::assertFalse($rule->isValid("bob"));
        self::assertFalse($rule->isValid("bob.com"));
        self::assertFalse($rule->isValid("bob@lewis"));
        self::assertFalse($rule->isValid("boblewis"));
    }

    public function testIsDate()
    {
        $rule = Rule::date();
        self::assertTrue($rule->isValid("2017-01-01"));
        self::assertTrue($rule->isValid("2016-01-01"));
        self::assertFalse($rule->isValid("-109-01-01"));
        self::assertFalse($rule->isValid("2016-31-31"));
        self::assertFalse($rule->isValid("2016-02-30"));
        self::assertFalse($rule->isValid("-109-01-01"));
        self::assertFalse($rule->isValid("2016"));
        self::assertFalse($rule->isValid("2017-50-03"));
    }

    public function testIsTime12Hours()
    {
        $rule = Rule::time12Hours();
        self::assertTrue($rule->isValid("08:07"));
        self::assertTrue($rule->isValid("00:00"));
        self::assertTrue($rule->isValid("01:56"));
        self::assertFalse($rule->isValid("23:45"));
        self::assertFalse($rule->isValid("-08:00"));
        self::assertFalse($rule->isValid("er"));
        self::assertFalse($rule->isValid("05:89"));
    }

    public function testIsTime24Hours()
    {
        $rule = Rule::time24Hours();
        self::assertTrue($rule->isValid("23:07"));
        self::assertTrue($rule->isValid("22:56"));
        self::assertTrue($rule->isValid("08:12"));
        self::assertTrue($rule->isValid("00:00"));
        self::assertFalse($rule->isValid("34:45"));
        self::assertFalse($rule->isValid("26:45"));
        self::assertFalse($rule->isValid("-08:00"));
        self::assertFalse($rule->isValid("er"));
        self::assertFalse($rule->isValid("05:89"));
    }

    public function testIsDateTime12Hours()
    {
        $rule = Rule::dateTime12Hours();
        self::assertTrue($rule->isValid("2019-01-01 11:07"));
        self::assertFalse($rule->isValid("2019-01-01 23:07"));
    }

    public function testIsDateTime24Hours()
    {
        $rule = Rule::dateTime24Hours();
        self::assertTrue($rule->isValid("2019-01-01 11:07"));
        self::assertTrue($rule->isValid("2019-01-01 23:07"));
        self::assertFalse($rule->isValid("2019-01-01 28:27"));
    }

    public function testIsPhone()
    {
        $rule = Rule::phone();
        self::assertTrue($rule->isValid("450-666-6666"));
        self::assertTrue($rule->isValid("(450) 555-5555"));
        self::assertTrue($rule->isValid("1-450-555-5555"));
        self::assertTrue($rule->isValid("1 (450) 555-5555"));
        self::assertFalse($rule->isValid("boby"));
        self::assertFalse($rule->isValid(""));
        self::assertFalse($rule->isValid("450-eee-3422"));
    }

    public function testIsAlpha()
    {
        $rule = Rule::alpha();
        self::assertTrue($rule->isValid("bob"));
        self::assertTrue($rule->isValid("test"));
        self::assertTrue($rule->isValid("Émilie"));
        self::assertFalse($rule->isValid("450-666-6666"));
        self::assertFalse($rule->isValid("bob129"));
        self::assertFalse($rule->isValid("dhhtgerg&@esjhgdkg"));
    }

    public function testIsName()
    {
        $rule = Rule::name("err");
        self::assertTrue($rule->isValid("Émilie Bornard"));
        self::assertTrue($rule->isValid("Nicolas Lacombe"));
        self::assertTrue($rule->isValid("Maxime Martel"));
        self::assertTrue($rule->isValid("Marc-Antoine Lemire"));
        self::assertTrue($rule->isValid("marc-antoine"));
        self::assertFalse($rule->isValid("450-666-6666"));
    }

    public function testIsZipCode()
    {
        $rule = Rule::zipCode();
        self::assertTrue($rule->isValid("35801"));
        self::assertTrue($rule->isValid("12345"));
        self::assertTrue($rule->isValid("12345-6789"));
        self::assertFalse($rule->isValid("1234"));
        self::assertFalse($rule->isValid("35801-0847984729847274"));
        self::assertFalse($rule->isValid("123456"));
        self::assertFalse($rule->isValid("1234-56789"));
    }

    public function testIsPostalCode()
    {
        $rule = Rule::postalCode();
        self::assertTrue($rule->isValid("j2p 2c7"));
        self::assertTrue($rule->isValid("J3R3L9"));
        self::assertTrue($rule->isValid("j3P 2g2"));
        self::assertFalse($rule->isValid("jjj 666"));
        self::assertFalse($rule->isValid("3J4 429"));
    }

    public function testIsAlphanumeric()
    {
        $rule = Rule::alphanumeric("err");
        self::assertTrue($rule->isValid("bob34"));
        self::assertTrue($rule->isValid("test"));
        self::assertTrue($rule->isValid("test1234"));
        self::assertFalse($rule->isValid("dslfj**"));
        self::assertFalse($rule->isValid("test+test"));
        self::assertFalse($rule->isValid("@bob"));
    }

    public function testIsUrl()
    {
        $rule = Rule::url();
        self::assertTrue($rule->isValid("http://www.google.ca"));
        self::assertTrue($rule->isValid("www.bob.com"));
        self::assertTrue($rule->isValid("http://www.bob.com"));
        self::assertTrue($rule->isValid("https://www.bob.com"));
        self::assertTrue($rule->isValid("www.bob.ca:80"));
        self::assertFalse($rule->isValid("allo.com"));
        self::assertFalse($rule->isValid("wsdghfggfdgh"));
        self::assertFalse($rule->isValid(""));
    }

    public function testIsYouTubeUrl()
    {
        $rule = Rule::youtubeUrl();
        self::assertTrue($rule->isValid('www.youtube.com/watch?v=DFYRQ_zQ-gk'));
        self::assertTrue($rule->isValid('http://www.youtube.com/watch?v=DFYRQ_zQ-gk'));
        self::assertTrue($rule->isValid('https://www.youtube.com/watch?v=DFYRQ_zQ-gk'));
        self::assertTrue($rule->isValid('m.youtube.com/watch?v=DFYRQ_zQ-gk'));
        self::assertTrue($rule->isValid('youtube.com/v/DFYRQ_zQ-gk?fs=1&hl=en_US'));
        self::assertTrue($rule->isValid('https://www.youtube.com/embed/DFYRQ_zQ-gk?autoplay=1'));
        self::assertTrue($rule->isValid('https://youtu.be/DFYRQ_zQ-gk?t=120'));
        self::assertTrue($rule->isValid('youtu.be/DFYRQ_zQ-gk'));
        self::assertFalse($rule->isValid('youtu.yu/DFYRQ_zQ-gk'));
        self::assertFalse($rule->isValid('www.youtobe.com/watch?v=DFYRQ_zQ-gk'));
    }

    public function testIsInRange()
    {
        $rule = Rule::range(0, 6);
        self::assertTrue($rule->isValid(4));
        self::assertFalse($rule->isValid(-5));
        self::assertFalse($rule->isValid(7));
    }

    public function testIsInArray()
    {
        $rule = Rule::inArray(["a", "b", "c"], "err");
        self::assertTrue($rule->isValid("b"));
        self::assertFalse($rule->isValid("e"));
    }

    public function testIsSameAs()
    {
        $rule = Rule::sameAs("password", "err");
        self::assertTrue($rule->isValid("1234", ["password" => "1234", "username" => "blewis"]));
        self::assertFalse($rule->isValid("1234", ["password" => "5678", "username" => "blewis"]));
        self::assertFalse($rule->isValid("1234", ["username" => "blewis"]));
    }

    public function testIsArray()
    {
        $rule = Rule::array("err");
        self::assertTrue($rule->isValid(["1", 2, "hello"]));
        self::assertFalse($rule->isValid("e"));
        self::assertTrue($rule->isValid(["bat" => "man"]));
    }

    public function testIsBoolean()
    {
        $rule = Rule::boolean("err");
        self::assertTrue($rule->isValid("true"));
        self::assertTrue($rule->isValid("false"));
        self::assertTrue($rule->isValid(false));
        self::assertTrue($rule->isValid(true));
        self::assertTrue($rule->isValid(0));
        self::assertTrue($rule->isValid(1));

        self::assertFalse($rule->isValid("hello"));
        self::assertFalse($rule->isValid("e"));
        self::assertFalse($rule->isValid(56));
    }

    public function testIsNotEmpty()
    {
        $rule = Rule::notEmpty();
        self::assertTrue($rule->isValid("hello"));
        self::assertFalse($rule->isValid(""));
    }

    public function testIsRegex()
    {
        $rule = Rule::regex("[a-e]{2}-[0-9]+");
        self::assertTrue($rule->isValid("ab-45"));
        self::assertTrue($rule->isValid("ab-1"));
        self::assertTrue($rule->isValid("aa-45968347982375"));
        self::assertFalse($rule->isValid("aa-"));
        self::assertFalse($rule->isValid("aa"));
        self::assertFalse($rule->isValid("fe-23"));
        self::assertFalse($rule->isValid("zz-zz"));
        self::assertFalse($rule->isValid(""));
    }
}
