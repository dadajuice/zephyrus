<?php namespace Zephyrus\Security;

use Zephyrus\Application\Configuration;

class Cryptography
{
    /**
     * Cryptographically hash a specified string using the default PHP hashing
     * algorithm. This method uses the default hash function included in the
     * PHP core and thus automatically provides a cryptographically random
     * salt. Optionally, a user defined salt can be specified, but is strongly
     * discouraged. Throws exception if hashing failed.
     *
     * @param string $string
     * @param string | null $salt
     * @param int | null $cost
     * @throws \RuntimeException
     * @return string
     */
    public static function hash(string $string, string $salt = null, int $cost = null): string
    {
        $hashOptions = [];
        if (!is_null($salt)) {
            $hashOptions['salt'] = $salt;
        }
        if (!is_null($cost)) {
            $hashOptions['cost'] = $cost;
        }

        $hash = password_hash($string, PASSWORD_DEFAULT, $hashOptions);
        if (!$hash) {
            throw new \RuntimeException("An error occurred in hashing process. Please review your configuration.");
        }
        return (string)$hash;
    }

    /**
     * Determines if the specified hash matches the given string.
     *
     * @param string $string
     * @param string $hash
     * @return bool
     */
    public static function verifyHash(string $string, string $hash): bool
    {
        return password_verify($string, $hash);
    }

    /**
     * Determines if a rehash is needed for the specified hash (e.g. used hash
     * algorithm changed or algorithm cost evolved). If this method returns
     * true, calling script would need to rehash and store the new hash.
     *
     * @param string $hash
     * @param string | null $salt
     * @param int | null $cost
     * @return bool
     */
    public static function isRehashNeeded(string $hash, string $salt = null, int $cost = null): bool
    {
        $hashOptions = [];
        if (!is_null($salt)) {
            $hashOptions['salt'] = $salt;
        }
        if (!is_null($cost)) {
            $hashOptions['cost'] = $cost;
        }
        return password_needs_rehash($hash, PASSWORD_DEFAULT, $hashOptions);
    }

    /**
     * Benchmark method to determine how high of a cost the current server can
     * afford without slowing down the server (somewhere between 8 - 10 is a
     * good baseline). Benchmark aims for ≤ 50 milliseconds stretching time.
     *
     * @see http://php.net/manual/en/function.password-hash.php
     * @return int
     */
    public static function findHighestHashCost(): int
    {
        $timeTarget = 0.05; // 50 milliseconds
        $cost = 8;
        do {
            $cost++;
            $start = microtime(true);
            password_hash("test", PASSWORD_BCRYPT, ["cost" => $cost]);
            $end = microtime(true);
        } while (($end - $start) < $timeTarget);
        return $cost;
    }

    /**
     * Returns a random hex of desired byte length.
     *
     * @param int $length
     * @return string
     * @throws \Exception
     */
    public static function randomHex(int $length = 128): string
    {
        $bytes = ceil($length / 2);
        $hex = bin2hex(self::randomBytes($bytes));
        return $hex;
    }

    /**
     * Returns a random integer between the provided min and max using random
     * bytes. Throws exception if min and max arguments have inconsistencies.
     *
     * @param int $min
     * @param int $max
     * @return int
     * @throws \Exception
     */
    public static function randomInt(int $min, int $max): int
    {
        if ($max <= $min) {
            throw new \Exception('Minimum equal or greater than maximum!');
        }
        if ($max < 0 || $min < 0) {
            throw new \Exception('Only positive integers supported for now!');
        }

        $difference = $max - $min;
        for ($power = 8; pow(2, $power) < $difference; $power = $power * 2) {
            ;
        }
        $powerExp = $power / 8;
        do {
            $randDiff = hexdec(bin2hex(self::randomBytes($powerExp)));
        } while ($randDiff > $difference);
        return $min + $randDiff;
    }

    /**
     * Returns a random string of the desired length using only the given
     * characters. If none is provided, alphanumeric characters ([0-9a-Z]) are
     * used.
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
     * Returns random bytes based on openssl. This method is used by all other
     * "random" methods. Throws an exception if the result is not considered
     * strong enough by the openssl lib.
     *
     * @param int $length
     * @throws \Exception
     * @returns string
     */
    public static function randomBytes(int $length = 1): string
    {
        $bytes = openssl_random_pseudo_bytes($length, $strong);
        if ($strong !== true) {
            throw new \Exception('OpenSSL Random byte generation insecure');
        }
        return $bytes;
    }

    /**
     * Encrypts the given data using the configured encryption algorithm and
     * the provided key. Returns a concatenation of the generated IV and the
     * cipher. Resulting cipher should only be decrypted using the decrypt
     * method.
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    public static function encrypt(string $data, string $key): string
    {
        $method = Configuration::getSecurityConfiguration('encryption_algorithm');
        $initializationVector = self::randomBytes(openssl_cipher_iv_length($method));
        $cipher = openssl_encrypt($data, $method, $key, 0, $initializationVector);
        return base64_encode($initializationVector) . ':' . base64_encode($cipher);
    }

    /**
     * Decrypts the given cipher using the configured encryption algorithm and
     * the provided decryption key. Throws exception if the cipher seems
     * invalid (which means it does not seem to include the IV).
     *
     * @param string $cipher
     * @param string $key
     * @return string
     * @throws \Exception
     */
    public static function decrypt(string $cipher, string $key): string
    {
        if (strpos($cipher, ':') === false) {
            throw new \Exception("Invalid cipher to decrypt");
        }
        $method = Configuration::getSecurityConfiguration('encryption_algorithm');
        list($initializationVector, $cipher) = explode(':', $cipher);
        $cipher = base64_decode($cipher);
        $initializationVector = base64_decode($initializationVector);
        return openssl_decrypt($cipher, $method, $key, 0, $initializationVector);
    }

    /**
     * Returns the required initialisation vector length for encryption based
     * on the configured algorithm.
     *
     * @return int
     */
    public static function getEncryptionIvLength(): int
    {
        return openssl_cipher_iv_length(Configuration::getSecurityConfiguration('encryption_algorithm'));
    }

    /**
     * Returns the configured encryption algorithm to be used in the
     * application.
     *
     * @return string
     */
    public static function getEncryptionAlgorithm(): string
    {
        return Configuration::getSecurityConfiguration('encryption_algorithm');
    }
}
