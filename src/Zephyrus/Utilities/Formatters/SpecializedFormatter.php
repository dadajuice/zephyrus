<?php namespace Zephyrus\Utilities\Formatters;

use Zephyrus\Utilities\Formatter;

trait SpecializedFormatter
{
    /**
     * Returns a SEO compatible url based on the specified string. Be sure to check the LC_CTYPE locale setting if
     * getting any question marks in result. Run locale -a on server to see full list of supported locales.
     *
     * @param string $name
     * @return string
     */
    public static function seourl(string $name): string
    {
        $url = mb_strtolower($name);
        $url = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $url);
        $url = preg_replace("/[^a-z0-9_\s-]/", "", $url);
        $url = preg_replace("/[\s-]+/", " ", $url);
        $url = trim($url);
        return preg_replace("/[\s_]/", "-", $url);
    }

    /**
     * Returns the most human-readable file size based on the size given in byte. If null is provided, as with the other
     * formats, the string "-" is returned.
     *
     * @param ?int $sizeInBytes
     * @param array $units
     * @return string
     */
    public static function filesize(?int $sizeInBytes, array $units = ['G' => 'gb', 'M' => 'mb', 'K' => 'kb', 'B' => 'bytes']): string
    {
        if (is_null($sizeInBytes)) {
            return "-";
        }
        $fileSize = $sizeInBytes;
        $unit = $units['B'];
        if ($sizeInBytes >= 1073741824) {
            $fileSize = round($sizeInBytes / 1024 / 1024 / 1024, 1);
            $unit = $units['G'];
        } elseif ($sizeInBytes >= 1048576) {
            $fileSize = round($sizeInBytes / 1024 / 1024, 1);
            $unit = $units['M'];
        } elseif ($sizeInBytes >= 1024) {
            $fileSize = round($sizeInBytes / 1024, 1);
            $unit = $units['K'];
        }
        return Formatter::decimal($fileSize, 0, 2) . ' ' . $unit;
    }

    public static function ellipsis(string $str, int $length = 50, string $concat = "..."): string
    {
        return (strlen($str) > $length) ? substr($str, 0, $length) . $concat : $str;
    }
}
