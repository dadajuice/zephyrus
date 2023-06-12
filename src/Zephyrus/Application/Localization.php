<?php namespace Zephyrus\Application;

use stdClass;
use Zephyrus\Exceptions\LocalizationException;
use Zephyrus\Security\Cryptography;
use Zephyrus\Utilities\FileSystem\Directory;

class Localization
{
    private const GENERATED_CLASS_NAME = "Localize";
    private const DEFAULT_NAMESPACE = "Locale";
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
        $lastConstant = array_pop($segments);
        $object = null;

        try {
            foreach ($segments as $segment) {
                $object = (is_null($object))
                    ? call_user_func(self::DEFAULT_NAMESPACE . "\\$locale\\Localize::$segment")
                    : $object::{$segment}();
            }
        } catch (\Error $e) {
            return $key;
        }

        if (is_null($object)) { // Localize class not found
            return $key;
        }

        $constant = sprintf('%s::%s', get_class($object), $lastConstant);
        if (!defined($constant)) {
            return $key;
        }
        $parameters[0] = constant($constant);
        $parameters = array_merge($parameters, $args);
        return call_user_func_array('sprintf', $parameters);
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
        list($constants, $methods, $classes) = $this->buildClassFile($locale, $globalArray);
        if (!empty($methods)) {
            $class = $this->createLocalizeClass($locale, self::GENERATED_CLASS_NAME, $constants, $methods, $classes);
            file_put_contents(ROOT_DIR . "/locale/cache/$locale/" . self::GENERATED_CLASS_NAME . ".php", $class);
        }
    }

    /**
     * Generates the Localize class file for the specified locale using the values from buildGlobalArrayFromJsonFiles.
     *
     * @param string $locale
     * @param array $array
     * @return array
     * @throws LocalizationException
     */
    private function buildClassFile(string $locale, array $array): array
    {
        $constants = "";
        $methods = [];
        $classes = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                list($returnedConstants, $returnedMethods, $returnedClasses) = $this->buildClassFile($locale, $value);
                $className = 'C' . Cryptography::randomString(20);
                $class = $this->createLocalizeClass($locale, $className, $returnedConstants, $returnedMethods, $returnedClasses);
                file_put_contents(ROOT_DIR . "/locale/cache/$locale/$className.php", $class);
                $methods[] = $key;
                $classes[] = $className;
            } else {
                $constants .= $this->addConstant($key, $value);
            }
        }
        return [$constants, $methods, $classes];
    }

    private function createLocalizeClass(string $locale, string $className, string $constants, array $methods, array $classes): string
    {
        $output = "<?php namespace " . self::DEFAULT_NAMESPACE . "\\$locale;" . PHP_EOL . PHP_EOL;
        if (!empty($classes)) {
            foreach ($classes as $class) {
                $output .= $this->addRequire($class);
            }
            $output .= PHP_EOL;
        }
        $output .= $this->startClass($className);
        if (!empty($constants)) {
            $output .= $constants . PHP_EOL;
        }
        if (!empty($methods)) {
            foreach ($methods as $i => $method) {
                $output .= $this->addMethod($method, $classes[$i]);
                $output .= PHP_EOL;
            }
        }
        $output .= $this->singleton();
        $output .= $this->endClass();
        return $output;
    }

    /**
     * Creates a constant string to be included into the cache class. Makes sure
     * to convert double quote into equivalent html entity to prevent PHP syntax
     * error in the resulting class.
     *
     * @param string $name
     * @param string $value
     * @throws LocalizationException
     * @return string
     */
    private function addConstant(string $name, string $value)
    {
        /*$reservedKeywords = [
            '__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone',
            'const', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare',
            'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally',
            'fn', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once',
            'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private',
            'protected', 'public', 'require', 'require_once', 'return', 'static', 'throw', 'trait', 'try',
            'unset', 'use', 'var', 'while', 'xor', 'yield', 'yield from'
        ];*/
        $reservedKeywords = ['__halt_compiler', 'class'];
        $compileConstants = [
            '__CLASS__', '__DIR__', '__FILE__', '__FUNCTION__', '__LINE__', '__METHOD__', '__NAMESPACE__',
            '__TRAIT__'
        ];
        if (in_array(strtolower($name), $reservedKeywords)) {
            throw new LocalizationException(LocalizationException::ERROR_RESERVED_WORD, "", $name);
        }
        if (in_array(strtoupper($name), $compileConstants)) {
            throw new LocalizationException(LocalizationException::ERROR_RESERVED_WORD, "", $name);
        }
        if (!preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $name)) {
            throw new LocalizationException(LocalizationException::ERROR_INVALID_NAMING, "", $name);
        }
        $value = str_replace('"', '&quot;', $value);
        $value = str_replace('$', '&#36;', $value);
        return "\tpublic const $name = \"$value\";" . PHP_EOL;
    }

    /**
     * Creates a method string to be included into the cache class which shall
     * allow to go into another cache class.
     *
     * @param string $name
     * @param string $className
     * @return string
     */
    private function addMethod(string $name, string $className)
    {
        return "\tpublic static function $name(): $className" . PHP_EOL . "\t{" . PHP_EOL . "\t\treturn $className::getInstance();" . PHP_EOL . "\t}" . PHP_EOL;
    }

    private function addRequire(string $name)
    {
        return "require \"$name.php\";" . PHP_EOL;
    }

    private function startClass(string $className)
    {
        return "final class " . $className . PHP_EOL . "{" . PHP_EOL;
    }

    private function singleton()
    {
        $instanceVariable = "\tprivate static \$instance = null;" . PHP_EOL . PHP_EOL;
        $getInstanceMethod = "\tpublic static function getInstance(): self" . PHP_EOL . "\t{" . PHP_EOL . "\t\tif (is_null(self::\$instance)) {" . PHP_EOL . "\t\t\tself::\$instance = new self();" . PHP_EOL . "\t\t}" . PHP_EOL . "\t\treturn self::\$instance;" . PHP_EOL . "\t}" . PHP_EOL . PHP_EOL;
        $privateConstructor = "\tprivate function __construct() {}" . PHP_EOL;
        return $instanceVariable . $getInstanceMethod . $privateConstructor;
    }

    private function endClass()
    {
        return "}" . PHP_EOL;
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
     * @return array
     * @throws LocalizationException
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
        $directory = new Directory(ROOT_DIR . '/locale/' . $locale);
        $parts = explode("_", $locale);
        return (object) [
            'locale' => $locale,
            'lang_code' => $parts[0],
            'country_code' => $parts[1],
            'flag_emoji' => self::getFlagEmoji($parts[1]),
            'country' => locale_get_display_region($locale),
            'lang' => locale_get_display_language($locale),
            'count' => count($directory->getFilenames()),
            'size' => $directory->size()
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
