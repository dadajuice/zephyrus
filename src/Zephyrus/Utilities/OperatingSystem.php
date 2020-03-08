<?php namespace Zephyrus\Utilities;

use stdClass;

class OperatingSystem
{
    public static function diskStats(string $partitionPath = "/var/www"): stdClass
    {
        $df = disk_free_space($partitionPath);
        $dt = disk_total_space($partitionPath);
        return (object) [
            'free' => $df,
            'total' => $dt,
            'used' => $dt - $df,
            'percent' => ($dt - $df) / $dt
        ];
    }

    public static function getName()
    {
    }

    public static function getCpu()
    {
    }

    public static function getMemory()
    {
    }
}
