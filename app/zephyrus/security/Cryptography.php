<?php namespace Zephyrus\Security;

/**
 * BASED ON Class CryptoLib (v0.8 Christmas)
 * Created by Junade Ali
 * Requires OpenSSL, MCrypt > 2.4.x, PHP 5.3.0+
 *
 * CryptoLib is an open-source PHP Cryptography library.
 * Copyright (C) 2014  Junade Ali
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
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
    public static function hash($string, $salt = null, $cost = null)
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
            throw new \RuntimeException("An error occurred in hashing process. Please review your PHP configuration.");
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
    public static function verifyHash($string, $hash)
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
     * @param string | null $cost
     * @return string
     */
    public static function isRehashNeeded($hash, $salt = null, $cost = null)
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
    public static function findHighestHashCost()
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
     * Random hex generator using pseudoBytes function in this class.
     * @param int $length
     * @return string
     * @throws \Exception
     */
    public static function randomHex($length = 128)
    {
        $bytes = ceil($length / 2);
        $hex = bin2hex(self::randomBytes($bytes));
        return $hex;
    }

    /**
     * Random integer generator using pseudoBytes function in this class.
     * @param $min
     * @param $max
     * @return mixed
     * @throws \Exception
     */
    public static function randomInt($min, $max)
    {
        if ($max <= $min) {
            throw new \Exception('Minimum equal or greater than maximum!');
        }
        if ($max < 0 || $min < 0) {
            throw new \Exception('Only positive integers supported for now!');
        }

        $difference = $max - $min;
        for ($power = 8; pow(2, $power) < $difference; $power = $power * 2) {;}
        $powerExp = $power / 8;
        do {
            $randDiff = hexdec(bin2hex(self::randomBytes($powerExp)));
        } while ($randDiff > $difference);
        return $min + $randDiff;
    }

    /**
     * Random string generator using randomInt function in this class.
     * @param $length
     * @param $characters
     * @return string
     */
    public static function randomString($length, $characters = null)
    {
        if (is_null($characters)) {
            $characters = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
        }
        $result = '';
        $n = count($characters);
        for ($i = 0; $i < $length; ++$i) {
            $result .= $characters[self::randomInt(0, $n - 1)];
        }
        return $result;
    }

    /**
     * Will return openssl_random_pseudo_bytes with desired length is $strong is set to true.
     * @param int $length
     * @throws \Exception
     * @returns int $bytes
     */
    public static function randomBytes($length = 1)
    {
        $bytes = openssl_random_pseudo_bytes($length, $strong);
        if ($strong === true) {
            return $bytes;
        } else {
            throw new \Exception ('OpenSSL Random byte generation insecure');
        }
    }
}