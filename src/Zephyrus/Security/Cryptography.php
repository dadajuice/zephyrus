<?php namespace Zephyrus\Security;

use InvalidArgumentException;
use stdClass;
use Zephyrus\Application\Configuration;

class Cryptography
{
    /**
     * Default algorithm to use with encrypt() and decrypt() methods if none is specified otherwise within the security
     * section of the config.ini configuration file as property [encryption_algorithm].
     */
    private const DEFAULT_ENCRYPTION_ALGORITHM = 'aes-256-cbc';

    /**
     * Cryptographically hash a specified string using the default PHP hashing algorithm. This method uses the default
     * hash function included in the PHP core and thus automatically provides a cryptographically random salt. If the
     * property [password_pepper] is defined in the security section of the config.ini file, the method will concatenate
     * the password with the configured pepper. This pepper should be unique by project and thus ensure that a given
     * hashed password will work only within a specific project. The pepper is designed to be a "secret" kept within the
     * server. Should be defined as a server environment to ensure maximum security.
     *
     * @param string $clearTextPassword
     * @param string $algorithm
     * @return string
     */
    public static function hashPassword(string $clearTextPassword, $algorithm = PASSWORD_DEFAULT): string
    {
        $pepper = Configuration::getSecurityConfiguration("password_pepper", null);
        if ($pepper) {
            $clearTextPassword = $clearTextPassword . $pepper;
        }
        return password_hash($clearTextPassword, $algorithm);
    }

    /**
     * Determines if the specified hash matches the given clear text password. Makes sure to add the pepper if one is
     * defines within the project's config.ini file. See hashPassword method for more information.
     *
     * @param string $clearTextPassword
     * @param string $hash
     * @return bool
     */
    public static function verifyHashedPassword(string $clearTextPassword, string $hash): bool
    {
        $pepper = Configuration::getSecurityConfiguration("password_pepper", null);
        if ($pepper) {
            $clearTextPassword = $clearTextPassword . $pepper;
        }
        return password_verify($clearTextPassword, $hash);
    }

    /**
     * Hashes the given string with the specified algorithm. By default, will do a basic md5 hashing. This method makes
     * sure to validate the support of the algorithm. Throws InvalidArgumentException otherwise.
     *
     * @param string $string
     * @param string $algorithm
     * @return string
     */
    public static function hash(string $string, string $algorithm = 'md5'): string
    {
        if (!in_array($algorithm, hash_algos())) {
            throw new InvalidArgumentException('Specified hashing algorithm not supported');
        }
        return hash($algorithm, $string);
    }

    /**
     * Hashes the entire content of the given file with the specified algorithm. By default, will do a basic md5
     * hashing. This method makes sure to validate the existence of the file and the support of the algorithm. Throws
     * InvalidArgumentException otherwise.
     *
     * @param string $filename
     * @param string $algorithm
     * @return string
     */
    public static function hashFile(string $filename, string $algorithm = 'md5'): string
    {
        if (!in_array($algorithm, hash_algos())) {
            throw new InvalidArgumentException('Specified hashing algorithm not supported');
        }
        if (!file_exists($filename)) {
            throw new InvalidArgumentException("Specified file to hash does not exist");
        }
        return hash_file($algorithm, $filename);
    }

    /**
     * Returns a random hex of desired length based on the openSSL cryptographic random.
     *
     * @param int $length
     * @return string
     */
    public static function randomHex(int $length = 128): string
    {
        $bytes = ceil($length / 2);
        return bin2hex(self::randomBytes($bytes));
    }

    /**
     * Returns a random integer between the provided min and max using random bytes based on the openSSL cryptographic
     * random. Throws InvalidArgumentException if min and max arguments have inconsistencies.
     *
     * @param int $min
     * @param int $max
     * @return int
     */
    public static function randomInt(int $min, int $max): int
    {
        if ($max <= $min) {
            throw new InvalidArgumentException('Minimum equal or greater than maximum!');
        }
        if ($max < 0 || $min < 0) {
            throw new InvalidArgumentException('Only positive integers supported for now!');
        }

        $difference = $max - $min;
        for ($power = 8; pow(2, $power) < $difference; $power = $power * 2) {
        }
        $powerExp = $power / 8;
        do {
            $randDiff = hexdec(bin2hex(self::randomBytes($powerExp)));
        } while ($randDiff > $difference);
        return $min + $randDiff;
    }

    /**
     * Returns a random string of the desired length using only the given characters. If none is provided, alphanumeric
     * characters ([0-9a-Z]) are used.
     *
     * @param int $length
     * @param string | array $characters
     * @return string
     */
    public static function randomString(int $length, $characters = null): string
    {
        if (is_null($characters)) {
            $characters = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
        }
        if (is_string($characters)) {
            $characters = str_split($characters);
        }
        $result = '';
        $characterCount = count($characters);
        for ($i = 0; $i < $length; ++$i) {
            $result .= $characters[self::randomInt(0, $characterCount - 1)];
        }
        return $result;
    }

    /**
     * Returns random bytes based on openssl. This method is used by all other "random" methods. Throws an exception if
     * the result is not considered strong enough by the openssl lib.
     *
     * @param int $length
     * @return string
     */
    public static function randomBytes(int $length = 1): string
    {
        return openssl_random_pseudo_bytes($length);
    }

    /**
     * Encrypts the given plain text using the configured encryption algorithm and the provided key. Includes a hash
     * authentication processing. Returns a concatenation of the authentication hash (hmac), the generated iv and the
     * cipher. By default, will encrypt using the AES CBC mode 256 bits (aes-256-cbc) algorithm. SHA256 is used to
     * derive hmac key. Use method decrypt to retrieve the original plain text.
     *
     * @param string $plainText
     * @param string $key
     * @return string
     */
    public static function encrypt(string $plainText, string $key): string
    {
        $algorithm = self::getEncryptionAlgorithm();
        $initializationVector = self::randomBytes(openssl_cipher_iv_length($algorithm));
        $keys = self::deriveEncryptionKey($key, $initializationVector); // password is the encryption key
        $encryptionKey  = mb_substr($keys, 0, 32, '8bit');
        $hashAuthenticationKey = mb_substr($keys, 32, null, '8bit');
        $cipher = openssl_encrypt($plainText, $algorithm, $encryptionKey, OPENSSL_RAW_DATA, $initializationVector);
        $hmac = hash_hmac('sha256', $initializationVector . $cipher, $hashAuthenticationKey);
        return base64_encode($hmac . $initializationVector . $cipher);
    }

    /**
     * Decrypts the given cipher using the configured encryption algorithm and the provided decryption key. Throws an
     * exception if the cipher seems invalid (not properly concatenated with the IV). Provider cipher should have been
     * made by the encrypt method. Returns the plain text or null if decryption failed. By default, will decrypt using
     * the AES CBC mode 256 bits (aes-256-cbc) algorithm.
     *
     * @param string $cipherText
     * @param string $key
     * @return null|string
     */
    public static function decrypt(string $cipherText, string $key): ?string
    {
        $cipherText = base64_decode($cipherText);
        if (strlen($cipherText) < 81) {
            throw new InvalidArgumentException('Invalid cipher provided');
        }
        $algorithm = self::getEncryptionAlgorithm();
        $hmac = mb_substr($cipherText, 0, 64, '8bit');
        $initializationVector = mb_substr($cipherText, 64, 16, '8bit');
        $cipher = mb_substr($cipherText, 80, null, '8bit');
        $keys = self::deriveEncryptionKey($key, $initializationVector); // password is the encryption key
        $encryptionKey  = mb_substr($keys, 0, 32, '8bit');
        $hashAuthenticationKey = mb_substr($keys, 32, null, '8bit');
        $hmacValidation = hash_hmac('sha256', $initializationVector . $cipher, $hashAuthenticationKey);
        if (!hash_equals($hmac, $hmacValidation)) {
            // Cipher authentication failed
            return null;
        }
        $plainText = openssl_decrypt($cipher, $algorithm, $encryptionKey, OPENSSL_RAW_DATA, $initializationVector);
        if ($plainText === false) {
            return null; // @codeCoverageIgnore
        }
        return $plainText;
    }

    /**
     * The aad argument (additional authenticated data) contains cleartext information that should be tested to
     * ensure there has been no alteration.
     *
     * @param string $plainText
     * @param string $key
     * @param string $aad
     * @return string
     */
    public static function authEncrypt(string $plainText, string $key, string $aad = ""): stdClass
    {
        $initializationVector = self::randomBytes(openssl_cipher_iv_length('aes-256-gcm'));
        $cipher = openssl_encrypt($plainText, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $initializationVector, $tag, $aad);
        return (object) [
            'cipher' => base64_encode($initializationVector) . ':' . base64_encode($cipher),
            'tag' => $tag,
        ];
    }

    public static function authDecrypt(string $cipher, string $key, string $tag, string $aad = "")
    {
        if (strpos($cipher, ':') === false) {
            throw new \InvalidArgumentException("Invalid cipher to decrypt");
        }
        list($initializationVector, $cipher) = explode(':', $cipher);
        $cipher = base64_decode($cipher);
        $initializationVector = base64_decode($initializationVector);
        return openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $initializationVector, $tag, $aad);
    }

    /**
     * Generates a key from a password based key derivation function (PBKDF) as defined in RFC2898. Uses the SHA256
     * hashing algorithm. This method is useful to attach an encryption key to a user based on his password.
     *
     * @see https://www.ietf.org/rfc/rfc2898.txt
     * @param string $password
     * @param string $salt
     * @param int $length
     * @return string
     */
    public static function deriveEncryptionKey(string $password, string $salt, int $length = 64): string
    {
        return hash_pbkdf2('sha256', $password, $salt, 80000, $length, true);
    }

    /**
     * Returns the configured baseline encryption algorithm to be used in the application with the encrypt and decrypt
     * methods.
     *
     * @return string
     */
    public static function getEncryptionAlgorithm(): string
    {
        return Configuration::getSecurityConfiguration('encryption_algorithm', self::DEFAULT_ENCRYPTION_ALGORITHM);
    }
}
