<?php namespace Zephyrus\Utilities;

class StringUtility
{
    public static function ellipsis(?string $string, int $length = 50): string
    {
        if (is_null($string)) {
            return "";
        }
        return (strlen($string) > $length)
            ? substr($string, 0, $length) . "..."
            : $string;
    }

    public static function acronym(?string $string, int $length = 2): string
    {
        if (is_null($string)) {
            return "";
        }
        $words = explode(" ", $string);
        if (count($words) > $length) {
            $words = array_chunk($words, $length)[0];
        }
        $acronym = "";
        foreach ($words as $w) {
            $acronym .= mb_substr($w, 0, $length - 1);
        }
        return $acronym;
    }

    public static function mark(?string $string, ?string $search): string
    {
        if (is_null($string)) {
            return "";
        }
        return (is_null($search))
            ? $string
            : preg_replace("/p{L}" . preg_quote($search, '/') . "/i", "<mark>$0</mark>", $string);
    }
}
