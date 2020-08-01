<?php namespace Zephyrus\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zephyrus\Security\Cryptography;

class CryptographyTest extends TestCase
{
    public function testSuccessfulDecryption()
    {
        $cipher = Cryptography::encrypt('test', 'batman');
        $message = Cryptography::decrypt($cipher, 'batman');
        self::assertEquals('test', $message);
    }

    public function testFailedDecryptionHmac()
    {
        $cipher = Cryptography::encrypt('test', 'batman');
        $cipher[0] = $cipher[0] ^ chr(1); // change bit in hmac part
        $message = Cryptography::decrypt($cipher, 'batman');
        self::assertNull($message);
    }

    public function testFailedDecryptionCipher()
    {
        $cipher = Cryptography::encrypt('test', 'batman');
        $cipher[85] = $cipher[85] ^ chr(1); // change bit in cipher part
        $message = Cryptography::decrypt($cipher, 'batman');
        self::assertNull($message);
    }

    public function testInvalidDecryption()
    {
        $this->expectException(InvalidArgumentException::class);
        Cryptography::decrypt('asdfgsfg43524534erwqf', 'batman'); // not good length
    }

    public function testInvalidRandomInt()
    {
        $this->expectException(InvalidArgumentException::class);
        Cryptography::randomInt(10, 10);
    }

    public function testInvalidNegativeRandomInt()
    {
        $this->expectException(InvalidArgumentException::class);
        Cryptography::randomInt(-5, 10);
    }

    public function testEncryptionAlgorithm()
    {
        // As defined in config.ini or default value
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

    public function testHashPassword()
    {
        $hash = Cryptography::hashPassword('batmanisbest');
        self::assertTrue(Cryptography::verifyHashedPassword('batmanisbest', $hash));
        self::assertFalse(Cryptography::verifyHashedPassword('batmanibest', $hash));
    }

    public function testHash()
    {
        $hash = Cryptography::hash('test', 'sha256');
        self::assertEquals('9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08', $hash);
    }

    public function testInvalidHash()
    {
        $this->expectException(InvalidArgumentException::class);
        Cryptography::hash('test', 'non_existing_algorithm');
    }

    public function testFileHash()
    {
        $hash = Cryptography::hashFile(ROOT_DIR . '/lib/images/batlike.jpg');
        self::assertEquals('6a7022c3626487d202bfb593b3d7db3d', $hash);
    }
}