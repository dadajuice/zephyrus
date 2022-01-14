<?php namespace Zephyrus\Security\IntrusionDetection;

use RuntimeException;
use Zephyrus\Utilities\Cache;

class IntrusionCache
{
    /**
     * Key used with APCU (PHP Cache) that keeps the previously loaded intrusion rule instances.
     */
    private const CACHE_KEY = "intrusion_detection_rules";

    /**
     * @var Cache
     */
    private Cache $cache;

    /**
     * Contains the loaded IntrusionRule instances available to the cache. Empty if not yet initiated or has been
     * previously cleared manually or system reboot.
     *
     * @var array
     */
    private array $rules = [];

    /**
     * Prepares the caching mechanism for the intrusion rules. The idea is to persist over requests and users the loaded
     * list of intrusion rules has it can reach high count and needs JSON parsing. Tries to load the rules if it exists
     * in PHP cache. Throws a RuntimeException if the APCu is not installed or enabled (and thus cannot be used).
     *
     * @throws RuntimeException
     */
    public function __construct()
    {
        $this->load();
    }

    /**
     * Retrieves intrusion rules loaded from the cache (if any exists).
     *
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Registers the intrusion rules previously parse from JSON file into the APCu (PHP Cache). Will stay in the cache
     * until it is manually removed with the clear() method or the server is rebooted.
     *
     * @param array $intrusionRules
     */
    public function cache(array $intrusionRules)
    {
        $this->cache->cache($intrusionRules);
        $this->rules = $intrusionRules;
    }

    /**
     * Removes the rules from APCu (PHP Cache).
     */
    public function clear()
    {
        $this->cache->clear();
        $this->rules = [];
    }

    /**
     * Retrieves the rules from the APCu (PHP Cache) if it exists.
     */
    private function load()
    {
        $this->cache = new Cache(self::CACHE_KEY);
        if ($this->cache->exists()) {
            $this->rules = (array) $this->cache->read();
        }
    }
}
