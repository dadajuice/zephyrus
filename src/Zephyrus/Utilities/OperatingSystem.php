<?php namespace Zephyrus\Utilities;

use stdClass;

class OperatingSystem
{
    public static function getDiskStats(string $partitionPath = "/var/www"): stdClass
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

    public static function getName(): stdClass
    {
        return (object) [
            'system' => php_uname("s"),
            'release' => php_uname("r"),
            'version' => php_uname("v"),
            'machine' => php_uname("m")
        ];
    }

    public static function getCpuAverageLoad()
    {
        return sys_getloadavg()[0];
    }

    public static function getMemoryUsage()
    {
        $free = shell_exec('free');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        return ($mem[2] / $mem[1]);
    }
}
