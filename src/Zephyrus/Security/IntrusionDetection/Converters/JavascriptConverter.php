<?php namespace Zephyrus\Security\IntrusionDetection\Converters;

/**
 * Concepts and code kindly obtained from the PHPIDS project with permission from original author.
 *
 * Defines a group of conversion methods aimed to provide anti-evasion mechanisms. Attackers can use obfuscation methods
 * to effectively hide a payload. These methods group JavaScript related technics.
 *
 * @see https://github.com/PHPIDS/PHPIDS
 * @author Mario Heiderich <mario.heiderich@gmail.com>
 * @author Christian Matthies <ch0012@gmail.com>
 * @author Lars Strojny <lars@strojny.net>
 */
trait JavascriptConverter
{
    /**
     * Checks for common charcode pattern and decodes them.
     *
     * @param string
     * @return string
     */
    private function convertFromJSCharCode(string $value): string
    {
        $matches = array();

        // check if value matches typical charCode pattern
        if (preg_match_all('/(?:[\d+-=\/\* ]+(?:\s?,\s?[\d+-=\/\* ]+)){4,}/ms', $value, $matches)) {
            $converted = '';
            $string    = implode(',', $matches[0]);
            $string    = preg_replace('/\s/', '', $string);
            $string    = preg_replace('/\w+=/', '', $string);
            $charcode  = explode(',', $string);

            foreach ($charcode as $char) {
                $char = preg_replace('/\W0/s', '', $char);

                if (preg_match_all('/\d*[+-\/\* ]\d+/', $char, $matches)) {
                    $match = preg_split('/(\W?\d+)/', implode('', $matches[0]), null, PREG_SPLIT_DELIM_CAPTURE);

                    if (array_sum($match) >= 20 && array_sum($match) <= 127) {
                        $converted .= chr(array_sum($match));
                    }

                } elseif (!empty($char) && $char >= 20 && $char <= 127) {
                    $converted .= chr($char);
                }
            }

            $value .= "\n" . $converted;
        }

        // check for octal charcode pattern
        if (preg_match_all('/(?:(?:[\\\]+\d+[ \t]*){8,})/ims', $value, $matches)) {
            $converted = '';
            $charcode  = explode('\\', preg_replace('/\s/', '', implode(',', $matches[0])));

            foreach (array_map('octdec', array_filter($charcode)) as $char) {
                if (20 <= $char && $char <= 127) {
                    $converted .= chr($char);
                }
            }
            $value .= "\n" . $converted;
        }

        // check for hexadecimal charcode pattern
        if (preg_match_all('/(?:(?:[\\\]+\w+\s*){8,})/ims', $value, $matches)) {
            $converted = '';
            $charcode  = explode('\\', preg_replace('/[ux]/', '', implode(',', $matches[0])));

            foreach (array_map('hexdec', array_filter($charcode)) as $char) {
                if (20 <= $char && $char <= 127) {
                    $converted .= chr($char);
                }
            }
            $value .= "\n" . $converted;
        }

        return $value;
    }

    /**
     * Eliminate JS regex modifiers.
     *
     * @param string
     * @return string
     */
    private function convertJSRegexModifiers(string $value): string
    {
        return preg_replace('/\/[gim]+/', '/', $value);
    }

    /**
     * This method converts JS unicode code points to regular characters.
     *
     * @param string
     * @return string
     */
    private function convertFromJSUnicode(string $value): string
    {
        $matches = array();
        preg_match_all('/\\\u[0-9a-f]{4}/ims', $value, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $match) {
                $chr = chr(hexdec(substr($match, 2, 4)));
                $value = str_replace($match, $chr, $value);
            }
            $value .= "\n\u0001";
        }
        return $value;
    }
}
