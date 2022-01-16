<?php namespace Zephyrus\Tests\Security;

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

    public function testSuccessfulFileDecryption()
    {
        Cryptography::encryptFile(ROOT_DIR . '/secrets.txt', 'secretlock', ROOT_DIR . '/secrets-temp.txt');
        self::assertTrue(file_exists(ROOT_DIR . '/secrets-temp.txt'));
        self::assertNotEquals('im batman', file_get_contents(ROOT_DIR . '/secrets-temp.txt'));
        Cryptography::decryptFile(ROOT_DIR . '/secrets-temp.txt', 'secretlock');
        self::assertEquals('im batman', file_get_contents(ROOT_DIR . '/secrets-temp.txt'));
        unlink(ROOT_DIR . '/secrets-temp.txt');
    }

    public function testInexistantFileEncryption()
    {
        self::expectException(InvalidArgumentException::class);
        Cryptography::encryptFile(ROOT_DIR . '/jkhdsfkhsdf.txt', 'secretlock');
    }

    public function testInexistantFileDecryption()
    {
        self::expectException(InvalidArgumentException::class);
        Cryptography::decryptFile(ROOT_DIR . '/jkhdsfkhsdf.txt', 'secretlock');
    }

    public function testFailedDecryption()
    {
        $cipher = Cryptography::encrypt('test', 'batman');
        $i = rand(0, mb_strlen($cipher, '8bit') - 1);
        $cipher[$i] = $cipher[$i] ^ chr(1); // random changes
        $message = Cryptography::decrypt($cipher, 'batman');
        self::assertNull($message);
    }

    public function testInvalidDecryption()
    {
        $message = Cryptography::decrypt('asdfgsfg43524534erwqf', 'batman'); // not good length
        self::assertNull($message);
    }

    public function testSuccessfulAuthDecryption()
    {
        $cipher = Cryptography::authEncrypt("i am a secret", "ksd34289esfnfs93wjnes920", "batman");
        $message = Cryptography::authDecrypt($cipher, "ksd34289esfnfs93wjnes920", "batman");
        self::assertEquals("i am a secret", $message);
    }

    public function testFailedAuthDecryptionStructure()
    {
        $cipher = base64_encode('ljsdhfkdsfkdsfhfd');
        $message = Cryptography::authDecrypt($cipher, "ksd34289esfnfs93wjnes920", "batman");
        self::assertNull($message);
    }

    public function testFailedAuthDecryptionByHmac()
    {
        $cipher = base64_encode('ljsdhfkdsfkdsfhfds/+jdfhkjgkhdfg/+lkdsjfjksdf');
        $message = Cryptography::authDecrypt($cipher, "ksd34289esfnfs93wjnes920", "batman");
        self::assertNull($message);
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

    public function testInvalidFileHash()
    {
        $this->expectException(InvalidArgumentException::class);
        Cryptography::hashFile(ROOT_DIR . '/lib/images/batjsdhfkdshfkhjfsdlike.jpg');
    }

    public function testInvalidFileHashAlgorithm()
    {
        $this->expectException(InvalidArgumentException::class);
        Cryptography::hashFile(ROOT_DIR . '/lib/images/batlike.jpg', 'non_existing_algorithm');
    }
}