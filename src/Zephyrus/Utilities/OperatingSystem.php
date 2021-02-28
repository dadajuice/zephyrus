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

    /**
     * Retrieves the percent representation of the average CPU load.
     *
     * @return float
     */
    public static function getCpuAverageLoad(): float
    {
        return sys_getloadavg()[0];
    }

    /**
     * Retrieves the percent representation of the memory used on the system.
     *
     * @return float
     */
    public static function getMemoryUsage(): float
    {
        $free = shell_exec('free');
        $free = (string) trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        return $mem[2] / $mem[1];
    }

    /**
     * Retrieves the current established connections for the specified port.
     *
     * @param int $port
     * @return mixed
     */
    public static function getActiveConnections(int $port = 443)
    {
        //return `netstat -an | grep $port | grep tcp | grep -v 0.0.0.0 | grep -v ::: | cut -d':' -f2 | cut -d' ' -f12 | sort | uniq | wc -l;`;
        return `netstat -an | grep $port | grep tcp | grep -v 0.0.0.0 | grep -v ::: | grep ESTABLISHED | wc -l`;
    }

    /**
     * @return int
     */
    public static function getCurrentMemory(): int
    {
        return memory_get_usage();
    }

    /**
     * @return int
     */
    public static function getCurrentAllocatedMemory(): int
    {
        return memory_get_usage(true);
    }

    /**
     * Retrieves the memory script peak in bytes for profiling. This is not the real memory actually allocated by PHP,
     * this is the memory used for the current script. Should be used for profiling. In reality, PHP will allocate more
     * memory for the script than its peak.
     *
     * @see getAllocatedMemoryPeak
     * @return int
     */
    public static function getMemoryPeak(): int
    {
        return memory_get_peak_usage();
    }

    /**
     * Retrieves the script's peak allocated memory in bytes. This is the REAL memory used at peak by PHP for the
     * running script, PHP allocates memory in chunks depending on the overall usage.
     *
     * @return int
     */
    public static function getAllocatedMemoryPeak(): int
    {
        return memory_get_peak_usage(true);
    }

    /**
     * Retrieves the process owner of the running script (e.g www-data).
     *
     * @return string
     */
    public static function getProcessOwner(): string
    {
        return posix_getpwuid(posix_geteuid())['name'];
    }

    /**
     * Retrieves the owner of the given path (either directory or file).
     *
     * @param string $path
     * @return string
     */
    public static function getOwner(string $path): string
    {
        return posix_getpwuid(fileowner($path))['name'];
    }

    /**
     * Retrieves the group of the given path (either directory or file).
     *
     * @param string $path
     * @return string
     */
    public static function getGroup(string $path): string
    {
        return posix_getpwuid(filegroup($path))['name'];
    }
}
