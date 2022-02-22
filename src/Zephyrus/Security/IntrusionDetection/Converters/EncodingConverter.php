<?php namespace Zephyrus\Security\IntrusionDetection\Converters;

/**
 * Concepts and code kindly obtained from the PHPIDS project with permission from original author.
 *
 * Defines a group of conversion methods aimed to provide anti-evasion mechanisms. Attackers can use obfuscation methods
 * to effectively hide a payload. These methods group encoding related technics.
 *
 * @see https://github.com/PHPIDS/PHPIDS
 * @author Mario Heiderich <mario.heiderich@gmail.com>
 * @author Christian Matthies <ch0012@gmail.com>
 * @author Lars Strojny <lars@strojny.net>
 */
trait EncodingConverter
{
    /**
     * This method matches and translates base64 strings and fragments used in data URIs.
     *
     * @param string
     * @return string
     */
    private function convertFromNestedBase64(string $value): string
    {
        $matches = [];
        preg_match_all('/(?:^|[,&?])\s*([a-z0-9]{50,}=*)(?:\W|$)/im', $value, $matches);
        foreach ($matches[1] as $item) {
            if (isset($item) && !preg_match('/[a-f0-9]{32}/i', $item)) {
                $base64Item = base64_decode($item);
                $value = str_replace($item, $base64Item, $value);
            }
        }
        return $value;
    }

    /**
     * Converts relevant UTF-7 tags to UTF-8.
     *
     * @param string
     * @return string
     */
    private function convertFromUTF7(string $value): string
    {
        if (preg_match('/\+A\w+-?/m', $value)) {
            if (function_exists('mb_convert_encoding')) {
                $value .= "\n" . mb_convert_encoding($value, 'UTF-8', 'UTF-7');
            } else {
                // @codeCoverageIgnoreStart
                //list of all critical UTF7 codepoints
                $schemes = [
                    '+ACI-'      => '"',
                    '+ADw-'      => '<',
                    '+AD4-'      => '>',
                    '+AFs-'      => '[',
                    '+AF0-'      => ']',
                    '+AHs-'      => '{',
                    '+AH0-'      => '}',
                    '+AFw-'      => '\\',
                    '+ADs-'      => ';',
                    '+ACM-'      => '#',
                    '+ACY-'      => '&',
                    '+ACU-'      => '%',
                    '+ACQ-'      => '$',
                    '+AD0-'      => '=',
                    '+AGA-'      => '`',
                    '+ALQ-'      => '"',
                    '+IBg-'      => '"',
                    '+IBk-'      => '"',
                    '+AHw-'      => '|',
                    '+ACo-'      => '*',
                    '+AF4-'      => '^',
                    '+ACIAPg-'   => '">',
                    '+ACIAPgA8-' => '">'
                ];

                $value = str_ireplace(
                    array_keys($schemes),
                    array_values($schemes),
                    $value
                );
                // @codeCoverageIgnoreEnd
            }
        }

        return $value;
    }

    /**
     * This method collects and decodes proprietary encoding types.
     *
     * @param string
     * @return string
     */
    private function convertFromProprietaryEncodings(string $value): string
    {
        //Xajax error reportings
        $value = preg_replace('/<!\[CDATA\[(\W+)\]\]>/im', '$1', $value);

        //strip false alert triggering apostrophes
        $value = preg_replace('/(\w)\"(s)/m', '$1$2', $value);

        //strip quotes within typical search patterns
        $value = preg_replace('/^"([^"=\\!><~]+)"$/', '$1', $value);

        //OpenID login tokens
        $value = preg_replace('/{[\w-]{8,9}\}(?:\{[\w=]{8}\}){2}/', "", $value);

        //convert Content and \sdo\s to empty string
        $value = preg_replace('/Content|\Wdo\s/', "", $value);

        //strip emoticons
        $value = preg_replace(
            '/(?:\s[:;]-[)\/PD]+)|(?:\s;[)PD]+)|(?:\s:[)PD]+)|-\.-|\^\^/m',
            "",
            $value
        );

        //normalize separation char repetion
        $value = preg_replace('/([.+~=*_\-;])\1{2,}/m', '$1', $value);

        //normalize multiple single quotes
        $value = preg_replace('/"{2,}/m', '"', $value);

        //normalize quoted numerical values and asterisks
        $value = preg_replace('/"(\d+)"/m', '$1', $value);

        //normalize pipe separated request parameters
        $value = preg_replace('/\|(\w+=\w+)/m', '&$1', $value);

        //normalize ampersand listings
        $value = preg_replace('/(\w\s)&\s(\w)/', '$1$2', $value);

        //normalize escaped RegExp modifiers
        $value = preg_replace('/\/\\\(\w)/', '/$1', $value);

        return $value;
    }
}
