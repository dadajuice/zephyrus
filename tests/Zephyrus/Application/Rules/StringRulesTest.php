<?php namespace Zephyrus\Tests\Application\Rules;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Rule;

class StringRulesTest extends TestCase
{
    public function testIsNotEmpty()
    {
        $rule = Rule::notEmpty();
        self::assertTrue($rule->isValid("hello"));
        self::assertFalse($rule->isValid(""));
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

    public function testIsXml()
    {
        $rule = Rule::xml();
        self::assertTrue($rule->isValid("<root><head>tete</head><body>corps</body></root>"));
        self::assertFalse($rule->isValid("<root><head>tete</head>corps</body></root>"));
        self::assertFalse($rule->isValid("<root><head></root>"));
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
        self::assertTrue($rule->isValid("Francis d'Argenson"));
        self::assertTrue($rule->isValid("marc-antoine"));
        self::assertFalse($rule->isValid("450-666-6666"));
        self::assertFalse($rule->isValid("Tremblay, Jean"));
        self::assertFalse($rule->isValid("Yo! Batman? (Oui allo?)"));
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
        self::assertTrue($rule->isValid("École"));
        self::assertFalse($rule->isValid("dslfj**"));
        self::assertFalse($rule->isValid("test+test"));
        self::assertFalse($rule->isValid("@bob"));
    }

    public function testIsMinLength()
    {
        $rule = Rule::minLength(8);
        self::assertTrue($rule->isValid("OuiAllo12345"));
        self::assertFalse($rule->isValid("Oui"));
    }

    public function testIsMaxLength()
    {
        $rule = Rule::maxLength(4);
        self::assertTrue($rule->isValid("12"));
        self::assertTrue($rule->isValid("1234"));
        self::assertFalse($rule->isValid("12345"));
    }

    public function testIsPasswordCompliant()
    {
        $rule = Rule::passwordCompliant();
        self::assertTrue($rule->isValid('Omega1234'));
        self::assertFalse($rule->isValid('password'));
        self::assertFalse($rule->isValid('1234'));
        self::assertFalse($rule->isValid('test12345'));
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

    public function testIsRegexInsensitive()
    {
        $rule = Rule::regexInsensitive("[a-e]{2}-[0-9]+");
        self::assertTrue($rule->isValid("AB-1"));
    }

    public function testIsVariable()
    {
        $rule = Rule::variable();
        self::assertTrue($rule->isValid("mega"));
        self::assertFalse($rule->isValid("1234mega"));
    }

    public function testIsColor()
    {
        $rule = Rule::color();
        self::assertEquals("color", $rule->getName());
        self::assertTrue($rule->isValid("#000000"));
        self::assertTrue($rule->isValid("#00000000"));
        self::assertTrue($rule->isValid("#000000AA"));
        self::assertFalse($rule->isValid("#000000AABB"));
        self::assertFalse($rule->isValid("#000"));
        self::assertFalse($rule->isValid("#GGGGGG"));
        self::assertFalse($rule->isValid("AAAAAA"));
        self::assertFalse($rule->isValid("AAAAAA"));
    }

    public function testIsJson()
    {
        $rule = Rule::json();
        self::assertTrue($rule->isValid('{"name": "Bruce Wayne", "alias": "Batman"}'));
        self::assertTrue($rule->isValid('[{"name": "Bruce Wayne", "alias": "Batman"}, {"name": "Clark Kent", "alias": "Superman"}]'));
        self::assertFalse($rule->isValid('{"name": "Bruce Wayne", "alias": "Batman"'));
        self::assertFalse($rule->isValid('{"name": "Bruce Wayne", alias: "Batman"}'));
        self::assertFalse($rule->isValid('123'));
    }
}