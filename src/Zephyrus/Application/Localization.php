<?php namespace Zephyrus\Application;

use stdClass;
use Zephyrus\Exceptions\LocalizationException;
use Zephyrus\Utilities\FileSystem\Directory;

class Localization
{
    private static ?Localization $instance = null;

    /**
     * Currently loaded application locale language. Maps to a directory within /locale.
     *
     * @var string|null
     */
    private ?string $appLocale = null;

    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retrieves the list of all installed languages. Meaning all the directories under /locale. Will return an array
     * of stdClass containing all the details for each language.
     *
     * @return stdClass[]
     */
    public static function getInstalledLanguages(): array
    {
        $dirs = array_filter(glob(ROOT_DIR . '/locale/*'), 'is_dir');
        array_walk($dirs, function (&$value) {
            $value = basename($value);
        });
        $dirs = array_filter($dirs, function ($value) {
            return $value != "cache";
        });
        $languages = [];
        foreach ($dirs as $dir) {
            $languages[] = self::getLanguage($dir);
        }
        return $languages;
    }

    /**
     * Retrieves simply the names of the installed locales. For the complete object reference, use
     * getInstalledLanguages.
     *
     * @return string[]
     */
    public static function getInstalledLocales(): array
    {
        $results = [];
        foreach (self::getInstalledLanguages() as $language) {
            $results[] = $language->locale;
        }
        return $results;
    }

    /**
     * Retrieves the actual loaded language. Will return an stdClass containing all the details.
     *
     * @return stdClass
     */
    public function getLoadedLanguage(): stdClass
    {
        return self::getLanguage($this->appLocale);
    }

    /**
     * Initialize the localization environment. If no locale is given, it will be initialized with the default locale
     * set in the config.ini file.
     *
     * @param string|null $locale
     * @throws LocalizationException
     */
    public function start(?string $locale = null): void
    {
        $this->appLocale = $locale ?? Configuration::getLocaleConfiguration('language');
        $this->initializeLocale();
        $this->generate();
    }

    /**
     * @param string $locale
     * @throws LocalizationException
     */
    public function changeLanguage(string $locale): void
    {
        $this->start($locale);
    }

    public function getLoadedLocale(): string
    {
        return $this->appLocale;
    }

    public function localize(string $key, array $args = []): string
    {
        $locale = $this->appLocale;
        $segments = explode(".", $key);
        $localizeIdentifier = $segments[0];
        if (in_array($localizeIdentifier, self::getInstalledLocales())) {
            $locale = $localizeIdentifier;
            array_shift($segments);
        }

        $keys = require ROOT_DIR . "/locale/cache/$locale/generated.php";
        $result = null;
        foreach ($segments as $segment) {
            if (is_array($result)) {
                if (isset($result[$segment])) {
                    $result = $result[$segment];
                } else {
                    $result = null;
                    break;
                }
            } else {
                if (isset($keys[$segment])) {
                    $result = $keys[$segment];
                } else {
                    $result = null;
                    break;
                }
            }
        }

        if (is_null($result) || is_array($result)) { // Localize not found
            return $key;
        }

        $parameters[] = $result;
        return call_user_func_array('sprintf', array_merge($parameters, $args));
    }

    /**
     * Generates the localization cache for all installed languages if they are outdated or never created. Optionally,
     * you can force the whole regeneration with the boolean argument (will ignore the conditions and generate
     * everything from scratch). Throws an exception if json cannot be properly parsed.
     *
     * @param bool $force
     * @throws LocalizationException
     */
    public function generate(bool $force = false): void
    {
        foreach (self::getInstalledLocales() as $locale) {
            if ($force || $this->prepareCache($locale) || $this->isCacheOutdated($locale)) {
                $this->generateCache($locale);
            }
        }
    }

    /**
     * Generates a single language cache. Will completely remove any existing directories concerning this locale
     * beforehand and completely generate cache. Throws an exception if json cannot be properly parsed.
     *
     * @param string $locale
     * @throws LocalizationException
     */
    private function generateCache(string $locale): void
    {
        $this->clearCacheDirectory($locale);
        $globalArray = $this->buildGlobalArrayFromJsonFiles($locale);
        $arrayCode = '<?php' . PHP_EOL . '$localizeCache = ' . var_export($globalArray, true) . ';' . PHP_EOL . 'return $localizeCache;' . PHP_EOL;
        file_put_contents(ROOT_DIR . "/locale/cache/$locale/generated.php", $arrayCode);
    }

    /**
     * Verifies if the cache needs to be regenerated for the specified locale.
     *
     * @param string $locale
     * @return bool
     */
    private function isCacheOutdated(string $locale): bool
    {
        $lastModifiedLocaleJson = $this->getDirectoryLastModifiedTime(ROOT_DIR . "/locale/$locale");
        $lastModifiedLocaleCache = $this->getDirectoryLastModifiedTime(ROOT_DIR . "/locale/cache/$locale");
        return $lastModifiedLocaleJson > $lastModifiedLocaleCache;
    }

    /**
     * Creates the cache directory for the specified locale if they do not exist. Returns true if a directory was
     * created, false otherwise.
     *
     * @param string $locale
     * @return bool
     */
    private function prepareCache(string $locale): bool
    {
        $newlyCreated = false;
        if (!file_exists(ROOT_DIR . "/locale/cache")) {
            mkdir(ROOT_DIR . "/locale/cache");
            $newlyCreated = true;
        }
        if (!file_exists(ROOT_DIR . "/locale/cache/$locale")) {
            mkdir(ROOT_DIR . "/locale/cache/$locale");
            $newlyCreated = true;
        }
        return $newlyCreated;
    }

    /**
     * Builds an associative array containing all the json values to generate.
     *
     * @param string $locale
     * @throws LocalizationException
     * @return array
     */
    private function buildGlobalArrayFromJsonFiles(string $locale): array
    {
        $globalArray = [];
        foreach (recursiveGlob(ROOT_DIR . "/locale/$locale/*.json") as $file) {
            $string = file_get_contents($file);
            $jsonAssociativeArray = json_decode($string, true);
            $jsonLastError = json_last_error();
            if ($jsonLastError > JSON_ERROR_NONE) {
                throw new LocalizationException($jsonLastError, $file);
            }

            // Merge values if key exists from another file. Allows to have the same localization key in multiple files
            // and merge them at generation time.
            foreach ($jsonAssociativeArray as $key => $values) {
                $globalArray[$key] = (key_exists($key, $globalArray))
                    ? array_replace_recursive($globalArray[$key], $values)
                    : $values;
            }
        }
        return $globalArray;
    }

    private function getDirectoryLastModifiedTime($directory)
    {
        $lastModifiedTime = 0;
        $directoryLastModifiedTime = filemtime($directory);
        foreach (glob("$directory/*") as $file) {
            $fileLastModifiedTime = (is_file($file)) ? filemtime($file) : $this->getDirectoryLastModifiedTime($file);
            $lastModifiedTime = max($fileLastModifiedTime, $directoryLastModifiedTime, $lastModifiedTime);
        }
        return $lastModifiedTime;
    }

    private function initializeLocale(): void
    {
        $charset = Configuration::getLocaleConfiguration('charset');
        $locale = $this->appLocale . '.' . $charset;
        setlocale(LC_MESSAGES, $locale);
        setlocale(LC_TIME, $locale);
        setlocale(LC_CTYPE, $locale);
        putenv("LANG=" . $this->appLocale);
        date_default_timezone_set(Configuration::getLocaleConfiguration('timezone'));
    }

    private function __construct()
    {
    }

    /**
     * Removes the cache directory for the specified locale. Ignores if the directory does not exist (nothing to
     * empty).
     *
     * @param string $locale
     */
    private function clearCacheDirectory(string $locale): void
    {
        if (Directory::exists($locale)) {
            (new Directory(ROOT_DIR . "/locale/cache/$locale"))->remove();
        }
    }

    private static function getLanguage(string $locale): stdClass
    {
        $parts = explode("_", $locale);
        return (object) [
            'locale' => $locale,
            'lang_code' => $parts[0],
            'country_code' => $parts[1],
            'flag_emoji' => self::getFlagEmoji($parts[1]),
            'country' => locale_get_display_region($locale),
            'lang' => locale_get_display_language($locale)
        ];
    }

    /**
     * Converts the 2 letters country code into the corresponding flag emoji.
     *
     * @param string $countryCode
     * @return string
     */
    private static function getFlagEmoji(string $countryCode): string
    {
        $codePoints = array_map(function ($char) {
            return 127397 + ord($char);
        }, str_split(strtoupper($countryCode)));
        return mb_convert_encoding('&#' . implode(';&#', $codePoints) . ';', 'UTF-8', 'HTML-ENTITIES');
    }
}
