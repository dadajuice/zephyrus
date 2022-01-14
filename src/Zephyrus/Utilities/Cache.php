<?php namespace Zephyrus\Utilities;

use RuntimeException;

class Cache
{
    /**
     * @var string
     */
    private string $cacheKey;

    /**
     * Verifies if the APCu extension is currently installed and enabled.
     *
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return function_exists('apcu_enabled') && apcu_enabled();
    }

    /**
     * Instantiates a cache instance for the given key. Throws an exception is APCu is not supported.
     *
     * @param string $cacheKey
     */
    public function __construct(string $cacheKey)
    {
        if (!self::isAvailable()) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("APCu extension not installed or not enabled.");
            // @codeCoverageIgnoreEnd
        }
        $this->cacheKey = $cacheKey;
    }

    /**
     * Verifies if the cache key currently exists in the APCu (PHP Cache).
     *
     * @return bool
     */
    public function exists(): bool
    {
        return apcu_exists($this->cacheKey);
    }

    /**
     * Read the cached value corresponding the given key. If not value has been previously saved the method returns
     * null. Otherwise, it will return the value. The caller function should be prepared to type cast the returned
     * value.
     *
     * @return mixed
     */
    public function read(): mixed
    {
        if ($this->exists()) {
            return apcu_fetch($this->cacheKey);
        }
        return null;
    }

    /**
     * Removes the cached data from APCu (PHP Cache).
     */
    public function clear()
    {
        apcu_delete($this->cacheKey);
    }

    /**
     * Saves the given data into the APCu (PHP Cache). The time to live determines how many seconds the given data
     * should be cached. Default to 0 which means it will stay in cache until it is manually removed or the system is
     * rebooted. If some data already exists, it will be overwritten.
     *
     * @param mixed $data
     * @param int $timeToLive
     */
    public function cache(mixed $data, int $timeToLive = 0)
    {
        $result = apcu_store($this->cacheKey, $data, $timeToLive);
        if ($result === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Failed to cache specified data");
            // @codeCoverageIgnoreEnd
        }
    }
}
