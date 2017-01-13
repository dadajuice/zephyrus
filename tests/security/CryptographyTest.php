<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Security\Cryptography;

class CryptographyTest extends TestCase
{
    public function testEncryption()
    {
        $cipher = Cryptography::encrypt('test', 'batman');
        $message = Cryptography::decrypt($cipher, 'batman');
        self::assertEquals('test', $message);
    }

    /**
     * @expectedException \Exception
     */
    public function testInvalidDecryption()
    {
        Cryptography::decrypt('asdfgsfg43524534erwqf', 'batman');
    }

    /**
     * @expectedException \Exception
     */
    public function testInvalidRandomInt()
    {
        Cryptography::randomInt(10, 10);
    }

    /**
     * @expectedException \Exception
     */
    public function testInvalidNegativeRandomInt()
    {
        Cryptography::randomInt(-5, 10);
    }

    public function testEncryptionAlgorithm()
    {
        self::assertEquals('aes-256-cbc', Cryptography::getEncryptionAlgorithm());
    }

    public function testRandomString()
    {
        $result = Cryptography::randomString(4, 'aqs');
        self::assertEquals(4, strlen($result));
        self::assertEquals(1, preg_match('/^[aqs]{4}$/', $result));
    }

    public function testRandomHex()
    {
        $result = Cryptography::randomHex(6);
        self::assertEquals(1, preg_match('/^[0-9a-f]{6}$/', $result));
    }

    public function testHash()
    {
        $hash = Cryptography::hash('test');
        self::assertTrue(Cryptography::verifyHash('test', $hash));
    }

    public function testRehashNeeded()
    {
        $shaHash = '18EE24150DCB1D96752A4D6DD0F20DFD8BA8C38527E40AA8509B7ADECF78F9C6';
        $salt = '123456789012345678901234567890';
        $result = Cryptography::isRehashNeeded($shaHash, $salt);
        self::assertTrue($result);
    }
}