<?php namespace Zephyrus\Tests\Application\Form\Rules;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Rule;

class SpecializedRulesTest extends TestCase
{
    public function testIPv4All()
    {
        $rule = Rule::IPv4();
        self::assertTrue($rule->isValid("206.167.238.12"));
        self::assertTrue($rule->isValid("206.167.238.30"));
        self::assertTrue($rule->isValid("127.0.0.1")); // reserved
        self::assertTrue($rule->isValid("255.255.255.255")); // reserved
        self::assertTrue($rule->isValid("10.10.5.5")); // private
        self::assertTrue($rule->isValid("172.24.5.16")); // private
        self::assertTrue($rule->isValid("192.168.12.34")); // private
        self::assertFalse($rule->isValid("127.0"));
        self::assertFalse($rule->isValid("99.450.0.1"));
        self::assertFalse($rule->isValid("256.256.256.256"));
    }

    // 10.0.0.0 - 10.255.255.255
    // 172.16.0.0 – 172.31.255.255
    // 192.168.0.0 – 192.168.255.255
    public function testIPv4NoPrivate()
    {
        $rule = Rule::IPv4("", true, false);
        self::assertTrue($rule->isValid("206.167.238.12"));
        self::assertTrue($rule->isValid("206.167.238.30"));
        self::assertTrue($rule->isValid("127.0.0.1"));
        self::assertTrue($rule->isValid("255.255.255.255"));
        self::assertFalse($rule->isValid("10.10.5.5"));
        self::assertFalse($rule->isValid("172.16.10.10"));
        self::assertFalse($rule->isValid("192.168.10.10"));
    }

    // 0.0.0.0 - 0.255.255.255
    // 127.0.0.0 - 127.255.255.255
    // 169.254.0.0 – 169.254.255.255
    // 255.255.255.255
    public function testIPv4NoReserved()
    {
        $rule = Rule::IPv4("", false);
        self::assertTrue($rule->isValid("206.167.238.12"));
        self::assertTrue($rule->isValid("206.167.238.30"));
        self::assertFalse($rule->isValid("127.0.0.1"));
        self::assertFalse($rule->isValid("255.255.255.255"));
        self::assertFalse($rule->isValid("169.254.10.20"));
        self::assertFalse($rule->isValid("0.0.10.10"));
    }

    public function testIPv6All()
    {
        $rule = Rule::IPv6();
        self::assertTrue($rule->isValid("2001:0db8:85a3:0000:0000:8a2e:0370:7334"));
        self::assertFalse($rule->isValid("2001:0gg8:85a3:0000:0000:8a2e:0370:7334"));
        self::assertFalse($rule->isValid("2001:0db8:85a3:0000:0000:0370:7334"));
        self::assertFalse($rule->isValid("2001:0db8:85a30000:000:8a2e:0370:7334"));
    }

    public function testIpAddress()
    {
        $rule = Rule::ipAddress();
        self::assertTrue($rule->isValid("2001:0db8:85a3:0000:0000:8a2e:0370:7334"));
        self::assertTrue($rule->isValid("206.167.238.12"));
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

    // Do not test since it requires an internet connection
    /*public function testIsLiveUrl()
    {
        $rule = Rule::liveUrl();
        self::assertTrue($rule->isValid("https://google.com"));
        self::assertFalse($rule->isValid("https://lksdfksdfkhjsdfkjhfdskjhfdskjfdsjkhdfs.clkdsfh.com"));
    }*/

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
}