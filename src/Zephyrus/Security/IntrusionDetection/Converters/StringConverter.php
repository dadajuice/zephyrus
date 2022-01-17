<?php namespace Zephyrus\Security\IntrusionDetection\Converters;

/**
 * Concepts and code kindly obtained from the PHPIDS project with permission from original author.
 *
 * Defines a group of conversion methods aimed to provide anti-evasion mechanisms. Attackers can use obfuscation methods
 * to effectively hide a payload. These methods group string related technics.
 *
 * @see https://github.com/PHPIDS/PHPIDS
 * @author Mario Heiderich <mario.heiderich@gmail.com>
 * @author Christian Matthies <ch0012@gmail.com>
 * @author Lars Strojny <lars@strojny.net>
 */
trait StringConverter
{
    /**
     * Check for comments and erases them if available.
     *
     * @param string $value
     * @return string
     */
    private function convertFromCommented(string $value): string
    {
        // check for existing comments
        if (preg_match('/(?:\<!-|-->|\/\*|\*\/|\/\/\W*\w+\s*$)|(?:--[^-]*-)/ms', $value)) {
            $pattern = [
                '/(?:(?:<!)(?:(?:--(?:[^-]*(?:-[^-]+)*)--\s*)*)(?:>))/ms',
                '/(?:(?:\/\*\/*[^\/\*]*)+\*\/)/ms',
                '/(?:--[^-]*-)/ms'
            ];
            $converted = preg_replace($pattern, ';', $value);
            $value .= "\n" . $converted;
        }

        //make sure inline comments are detected and converted correctly
        $value = preg_replace('/(<\w+)\/+(\w+=?)/m', '$1/$2', $value);
        $value = preg_replace('/[^\\\:]\/\/(.*)$/m', '/**/$1', $value);
        $value = preg_replace('/([^\-&])#.*[\r\n\v\f]/m', '$1', $value);
        $value = preg_replace('/([^&\-])#.*\n/m', '$1 ', $value);
        $value = preg_replace('/^#.*\n/m', ' ', $value);
        return $value;
    }

    /**
     * Strip newlines.
     *
     * @param string
     * @return string
     */
    private function convertFromWhiteSpace(string $value): string
    {
        //check for inline linebreaks
        $search = ['\r', '\n', '\f', '\t', '\v'];
        $value = str_replace($search, ';', $value);
        // replace replacement characters regular spaces
        $value = str_replace('�', ' ', $value);
        //convert real linebreaks
        return preg_replace('/(?:\n|\r|\v)/m', '  ', $value);
    }

    /**
     * Converts from hex/dec entities.
     *
     * @param string
     * @return string
     */
    private function convertEntities(string $value): string
    {
        $converted = null;

        //deal with double encoded payload
        $value = preg_replace('/&amp;/', '&', $value);
        if (preg_match('/&#x?[\w]+/ms', $value)) {
            $converted = preg_replace('/(&#x?[\w]{2}\d?);?/ms', '$1;', $value);
            $converted = html_entity_decode($converted, ENT_QUOTES, 'UTF-8');
            $value .= "\n" . str_replace(';;', ';', $converted);
        }

        // normalize obfuscated protocol handlers
        $value = preg_replace(
            '/(?:j\s*a\s*v\s*a\s*s\s*c\s*r\s*i\s*p\s*t\s*:)|(d\s*a\s*t\s*a\s*:)/ms',
            'javascript:',
            $value
        );
        return $value;
    }

    /**
     * Normalize quotes.
     *
     * @param string
     * @return string
     */
    private function convertQuotes(string $value): string
    {
        // normalize different quotes to "
        $pattern = ['\'', '`', '´', '’', '‘'];
        $value = str_replace($pattern, '"', $value);
        //make sure harmless quoted strings don't generate false alerts
        $value = preg_replace('/^"([^"=\\!><~]+)"$/', '$1', $value);
        return $value;
    }

    /**
     * Detects nullbytes and controls chars via ord().
     *
     * @param string
     * @return string
     */
    private function convertFromControlChars(string $value): string
    {
        // critical ctrl values
        $search = [
            chr(0), chr(1), chr(2), chr(3), chr(4), chr(5),
            chr(6), chr(7), chr(8), chr(11), chr(12), chr(14),
            chr(15), chr(16), chr(17), chr(18), chr(19), chr(24),
            chr(25), chr(192), chr(193), chr(238), chr(255), '\\0'
        ];

        $value = str_replace($search, '%00', $value);

        //take care for malicious unicode characters
        $value = urldecode(
            preg_replace(
                '/(?:%E(?:2|3)%8(?:0|1)%(?:A|8|9)\w|%EF%BB%BF|%EF%BF%BD)|(?:&#(?:65|8)\d{3};?)/i',
                null,
                urlencode($value)
            )
        );
        $value = urlencode($value);
        $value = preg_replace('/(?:%F0%80%BE)/i', '>', $value);
        $value = preg_replace('/(?:%F0%80%BC)/i', '<', $value);
        $value = preg_replace('/(?:%F0%80%A2)/i', '"', $value);
        $value = preg_replace('/(?:%F0%80%A7)/i', '\'', $value);
        $value = urldecode($value);

        $value = preg_replace('/(?:%ff1c)/', '<', $value);
        $value = preg_replace('/(?:&[#x]*(200|820|200|820|zwn?j|lrm|rlm)\w?;?)/i', null, $value);
        $value = preg_replace(
            '/(?:&#(?:65|8)\d{3};?)|' .
            '(?:&#(?:56|7)3\d{2};?)|' .
            '(?:&#x(?:fe|20)\w{2};?)|' .
            '(?:&#x(?:d[c-f])\w{2};?)/i',
            null,
            $value
        );

        $value = str_replace(
            [
                '«',
                '〈',
                '＜',
                '‹',
                '〈',
                '⟨'
            ],
            '<',
            $value
        );
        $value = str_replace(
            [
                '»',
                '〉',
                '＞',
                '›',
                '〉',
                '⟩'
            ],
            '>',
            $value
        );

        return $value;
    }

    /**
     * Detects nullbytes and controls chars via ord().
     *
     * @param string
     * @return string
     */
    private function convertFromOutOfRangeChars(string $value): string
    {
        $values = str_split($value);
        foreach ($values as $item) {
            if (ord($item) >= 127) {
                $value = str_replace($item, ' ', $value);
            }
        }
        return $value;
    }

    /**
     * Strip XML patterns.
     *
     * @param string
     * @return string
     */
    private function convertFromXML(string $value): string
    {
        $converted = strip_tags($value);
        if (!$converted || $converted === $value) {
            return $value;
        } else {
            return $value . "\n" . $converted;
        }
    }

    /**
     * Converts basic concatenations.
     *
     * @param string
     * @return string
     */
    private function convertFromConcatenated(string $value): string
    {
        //normalize remaining backslashes
        if ($value != preg_replace('/(\w)\\\/', "$1", $value)) {
            $value .= preg_replace('/(\w)\\\/', "$1", $value);
        }

        $compare = stripslashes($value);

        $pattern = [
            '/(?:<\/\w+>\+<\w+>)/s',
            '/(?:":\d+[^"[]+")/s',
            '/(?:"?"\+\w+\+")/s',
            '/(?:"\s*;[^"]+")|(?:";[^"]+:\s*")/s',
            '/(?:"\s*(?:;|\+).{8,18}:\s*")/s',
            '/(?:";\w+=)|(?:!""&&")|(?:~)/s',
            '/(?:"?"\+""?\+?"?)|(?:;\w+=")|(?:"[|&]{2,})/s',
            '/(?:"\s*\W+")/s',
            '/(?:";\w\s*\+=\s*\w?\s*")/s',
            '/(?:"[|&;]+\s*[^|&\n]*[|&]+\s*"?)/s',
            '/(?:";\s*\w+\W+\w*\s*[|&]*")/s',
            '/(?:"\s*"\s*\.)/s',
            '/(?:\s*new\s+\w+\s*[+",])/',
            '/(?:(?:^|\s+)(?:do|else)\s+)/',
            '/(?:[{(]\s*new\s+\w+\s*[)}])/',
            '/(?:(this|self)\.)/',
            '/(?:undefined)/',
            '/(?:in\s+)/'
        ];

        // strip out concatenations
        $converted = preg_replace($pattern, null, $compare);

        //strip object traversal
        $converted = preg_replace('/\w(\.\w\()/', "$1", $converted);

        // normalize obfuscated method calls
        $converted = preg_replace('/\)\s*\+/', ")", $converted);

        //convert JS special numbers
        $converted = preg_replace(
            '/(?:\(*[.\d]e[+-]*[^a-z\W]+\)*)|(?:NaN|Infinity)\W/ims',
            1,
            $converted
        );

        if ($converted && ($compare != $converted)) {
            $value .= "\n" . $converted;
        }

        return $value;
    }
}
