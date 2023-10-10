<?php namespace Zephyrus\Security;

use InvalidArgumentException;
use RuntimeException;
use Zephyrus\Application\Configuration;

class Cryptography
{
    /**
     * Default algorithm to use with encrypt() and decrypt() methods if none is specified otherwise within the security
     * section of the config.yml configuration file as property [encryption -> algorithm].
     */
    private const DEFAULT_ENCRYPTION_ALGORITHM = 'aes-256-cbc';

    /**
     * Default algorithm to use with hashPassword() if none is specified otherwise within the security section of the
     * config.yml configuration file as property [password -> algorithm].
     */
    private const DEFAULT_PASSWORD_HASH_ALGORITHM = PASSWORD_BCRYPT;

    /**
     * Default cost option to use with hashPassword() for BCRYPT if none is specified otherwise within the security
     * section of the config.yml configuration file as property [password -> options -> cost].
     */
    private const DEFAULT_PASSWORD_HASH_COST = 13;

    /**
     * Cryptographically hash a specified string using the default PHP hashing algorithm. This method uses the default
     * hash function included in the PHP core and thus automatically provides a cryptographically random salt. If the
     * property [pepper] is defined in the password security section of the config.yml file, the method will concatenate
     * the password with the configured pepper.
     *
     * This pepper should be unique by project and thus ensure that a given hashed password will work only within a
     * specific project. The pepper is designed to be a "secret" kept within the server. Should be defined as a server
     * environment to ensure maximum security.
     *
     * The algorithm used by default is PASSWORD_CRYPT but can be changed with the property [algorithm] in the password
     * security section of the config.yml file as well as the hash options.
     *
     * @param string $clearTextPassword
     * @return string
     */
    public static function hashPassword(string $clearTextPassword): string
    {
        $config = Configuration::getSecurity("password");
        $pepper = $config['pepper'] ?? "";
        $algorithm = $config['algorithm'] ?? self::DEFAULT_PASSWORD_HASH_ALGORITHM;
        $options = $config['options'] ?? ['cost' => self::DEFAULT_PASSWORD_HASH_COST];
        if ($pepper) {
            $clearTextPassword = $clearTextPassword . $pepper;
        }
        return password_hash($clearTextPassword, $algorithm, $options);
    }

    /**
     * Determines if the specified hash matches the given clear text password. Makes sure to add the pepper if one is
     * defined within the project's config.yml file. See hashPassword method for more information.
     *
     * @param string $clearTextPassword
     * @param string $hash
     * @return bool
     */
    public static function verifyHashedPassword(string $clearTextPassword, string $hash): bool
    {
        $config = Configuration::getSecurity("password");
        $pepper = $config['pepper'] ?? "";
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
     * @param string|array|null $characters
     * @return string
     */
    public static function randomString(int $length, string|array $characters = null): string
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
     * @param string|null $key
     * @return string
     */
    public static function encrypt(string $plainText, ?string $key = null): string
    {
        $algorithm = self::getEncryptionAlgorithm();
        $key = !is_null($key) ? $key : self::getEncryptionDefaultKey();
        if (is_null($key)) {
            throw new RuntimeException("The encryption key cannot be null. Be sure to either give one specifically for the operation or set a default key within the config.yml file.");
        }

        $initializationVector = self::randomBytes(openssl_cipher_iv_length($algorithm));
        $keys = self::deriveEncryptionKey($key, $initializationVector); // password is the encryption key
        $encryptionKey = mb_substr($keys, 0, 32, '8bit');
        $hashAuthenticationKey = mb_substr($keys, 32, null, '8bit');
        $cipher = openssl_encrypt($plainText, $algorithm, $encryptionKey, OPENSSL_RAW_DATA, $initializationVector);
        $hmac = hash_hmac('sha256', $initializationVector . $cipher, $hashAuthenticationKey);
        return base64_encode($hmac . $initializationVector . $cipher);
    }

    /**
     * Decrypts the given cipher using the configured encryption algorithm and the provided decryption key. Provided
     * cipher should have been made by the encrypt method. Returns the plain text or null if decryption failed. By
     * default, will decrypt using the AES CBC mode 256 bits (aes-256-cbc) algorithm. Returns null if decryption fails.
     *
     * @param string $cipherText
     * @param string|null $key
     * @return null|string
     */
    public static function decrypt(string $cipherText, ?string $key = null): ?string
    {
        $algorithm = self::getEncryptionAlgorithm();
        $key = !is_null($key) ? $key : self::getEncryptionDefaultKey();
        if (is_null($key)) {
            throw new RuntimeException("The encryption key cannot be null. Be sure to either give one specifically for the operation or set a default key within the config.yml file.");
        }

        $cipherText = base64_decode($cipherText);
        if (strlen($cipherText) < 81) {
            return null;
        }

        $hmac = mb_substr($cipherText, 0, 64, '8bit');
        $initializationVector = mb_substr($cipherText, 64, 16, '8bit');
        $cipher = mb_substr($cipherText, 80, null, '8bit');
        $keys = self::deriveEncryptionKey($key, $initializationVector); // password is the encryption key
        $encryptionKey = mb_substr($keys, 0, 32, '8bit');
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
     * Encrypts the entire content of the given file with the specified key. This method overrides the original if no
     * destination is specified. Use the same algorithm as the encrypt function. This method makes sure to validate the
     * existence of the file and the support of the algorithm. Throws InvalidArgumentException otherwise. Warning! Make
     * sure to not lose the key because the file will forever be encrypted.
     *
     * @see encrypt
     * @param string $plainTextFilename
     * @param string $key
     * @param string|null $destination
     */
    public static function encryptFile(string $plainTextFilename, string $key, ?string $destination = null): void
    {
        if (!file_exists($plainTextFilename)) {
            throw new InvalidArgumentException("Specified file to encrypt does not exist");
        }
        $originalContent = file_get_contents($plainTextFilename);
        $cipherText = self::encrypt($originalContent, $key);
        file_put_contents($destination ?? $plainTextFilename, $cipherText);
    }

    /**
     * Decrypts the entire content of the given file with the specified key. This method overrides the original if no
     * destination is specified. Use the same algorithm as the decrypt function. This method makes sure to validate the
     * existence of the file and the support of the algorithm. Throws InvalidArgumentException otherwise.
     *
     * @see encrypt
     * @param string $cipherTextFilename
     * @param string $key
     * @param string|null $destination
     */
    public static function decryptFile(string $cipherTextFilename, string $key, ?string $destination = null): void
    {
        if (!file_exists($cipherTextFilename)) {
            throw new InvalidArgumentException("Specified file to decrypt does not exist");
        }
        $cipherText = file_get_contents($cipherTextFilename);
        $originalContent = self::decrypt($cipherText, $key);
        file_put_contents($destination ?? $cipherTextFilename, $originalContent);
    }

    /**
     * Encrypts the given plain text with the specified encryption key as usual but also authenticate with a hmac using
     * the Encrypt-then-MAC approach for authenticated encryption. The result contains the cipher, the hmac and salt.
     *
     * @param string $plainText
     * @param string $encryptionKey
     * @param string $authenticationKey
     * @return string
     */
    public static function authEncrypt(string $plainText, string $encryptionKey, string $authenticationKey): string
    {
        $cipher = self::encrypt($plainText, $encryptionKey);
        $salt = self::randomBytes(32);
        $hmac = hash_hmac('sha256', $cipher . $salt, $authenticationKey);
        return base64_encode($cipher . '/+' . $hmac . '/+' . $salt);
    }

    /**
     * Decrypts the given cipher text using the encryption key after authenticating the hmac with the given
     * authentication key. Returns null if decryption fails.
     *
     * @param string $cipherText
     * @param string $encryptionKey
     * @param string $authenticationKey
     * @return string|null
     */
    public static function authDecrypt(string $cipherText, string $encryptionKey, string $authenticationKey): ?string
    {
        $rawCipherText = base64_decode($cipherText);
        if (substr_count($rawCipherText, '/+') != 2) {
            return null;
        }
        list($cipher, $hmac, $salt) = explode('/+', $rawCipherText);
        $hmacNow = hash_hmac('sha256', $cipher . $salt, $authenticationKey);
        if (!hash_equals($hmac, $hmacNow)) {
            return null;
        }
        return self::decrypt($cipher, $encryptionKey);
    }

    /**
     * Generates a key from a password based key derivation function (PBKDF) as defined in RFC2898. Uses the SHA256
     * hashing algorithm. This method is useful to attach an encryption key to a user based on his password. The
     * iteration count will greatly affect performances, be sure to use something adapted to your server capacity.
     *
     * @see https://www.ietf.org/rfc/rfc2898.txt
     * @param string $password
     * @param string $salt
     * @param int $length
     * @param int $iteration
     * @return string
     */
    public static function deriveEncryptionKey(string $password, string $salt, int $length = 64, int $iteration = 80000): string
    {
        return hash_pbkdf2('sha256', $password, $salt, $iteration, $length);
    }

    /**
     * Returns the configured baseline encryption algorithm to be used in the application with encrypt and decrypt
     * methods.
     *
     * @return string
     */
    public static function getEncryptionAlgorithm(): string
    {
        $config = Configuration::getSecurity("encryption");
        return $config['algorithm'] ?? self::DEFAULT_ENCRYPTION_ALGORITHM;
    }

    /**
     * Returns the configured default encryption key to be used in the application with encrypt and decrypt
     * methods. Returns null if no default key has been specified.
     *
     * @return string|null
     */
    public static function getEncryptionDefaultKey(): ?string
    {
        $config = Configuration::getSecurity("encryption");
        return $config['key'] ?? self::DEFAULT_ENCRYPTION_ALGORITHM;
    }
}
