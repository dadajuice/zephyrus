<?php namespace Zephyrus\Application;

use Zephyrus\Exceptions\LocalizationException;
use Zephyrus\Security\Cryptography;

class Localization
{
    public const GENERATED_CLASS_NAME = "Localize";
    private const SESSION_LANGUAGE_KEY = "__zephyrus_lang";

    /**
     * @var Localization
     */
    private static $instance = null;

    /**
     * @var string
     */
    private $appLocale;

    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function localize($key, array $args = []): string
    {
        $segments = explode(".", $key);
        $lastConstant = array_pop($segments);
        $object = null;

        try {
            foreach ($segments as $segment) {
                $object = (is_null($object)) ? \Localize::{$segment}() : $object::{$segment}();
            }
        } catch (\Error $e) {
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

    public function changeLocale($locale)
    {
        Session::getInstance()->set(self::SESSION_LANGUAGE_KEY, $locale);
    }

    /**
     * @param bool $force
     * @throws LocalizationException
     */
    public function generate(bool $force = false)
    {
        if ($force || $this->prepareCache() || $this->isCacheOutdated()) {
            $globalArray = $this->buildGlobalArrayFromJsonFiles();
            list($constants, $methods, $classes) = $this->buildClassFile($globalArray);
            if (!empty($methods)) {
                $className = self::GENERATED_CLASS_NAME;
                $class = $this->createLocalizeClass($className, $constants, $methods, $classes);
                file_put_contents(ROOT_DIR . "/locale/cache/{$this->appLocale}/" . self::GENERATED_CLASS_NAME . ".php", $class);
            }
        }
        if (!class_exists(self::GENERATED_CLASS_NAME)) {
            require ROOT_DIR . "/locale/cache/{$this->appLocale}/Localize.php";
        }
    }

    private function buildClassFile($array)
    {
        $constants = "";
        $methods = [];
        $classes = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                list($returnedConstants, $returnedMethods, $returnedClasses) = $this->buildClassFile($value);
                $className = 'C' . Cryptography::randomString(20);
                $class = $this->createLocalizeClass($className, $returnedConstants, $returnedMethods, $returnedClasses);
                file_put_contents(ROOT_DIR . "/locale/cache/{$this->appLocale}/$className.php", $class);
                $methods[] = $key;
                $classes[] = $className;
            } else {
                $constants .= $this->addConstant($key, $value);
            }
        }
        return [$constants, $methods, $classes];
    }

    private function createLocalizeClass(string $className, string $constants, array $methods, array $classes)
    {
        $output = "<?php" . PHP_EOL . PHP_EOL;
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

    private function addConstant($name, $value)
    {
        return "\tpublic const $name = \"$value\";" . PHP_EOL;
    }

    private function addMethod($name, $className)
    {
        return "\tpublic static function $name(): $className" . PHP_EOL . "\t{" . PHP_EOL . "\t\treturn $className::getInstance();" . PHP_EOL . "\t}" . PHP_EOL;
    }

    private function addRequire($name)
    {
        return "require \"$name.php\";" . PHP_EOL;
    }

    private function startClass($className)
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

    private function isCacheOutdated()
    {
        $lastModifiedLocaleJson = $this->getDirectoryLastModifiedTime(ROOT_DIR . "/locale/{$this->appLocale}");
        $lastModifiedLocaleCache = $this->getDirectoryLastModifiedTime(ROOT_DIR . "/locale/cache/{$this->appLocale}");
        return $lastModifiedLocaleJson > $lastModifiedLocaleCache;
    }

    private function prepareCache()
    {
        $newlyCreated = false;
        if (!file_exists(ROOT_DIR . "/locale/cache")) {
            mkdir(ROOT_DIR . "/locale/cache");
            $newlyCreated = true;
        }
        if (!file_exists(ROOT_DIR . "/locale/cache/{$this->appLocale}")) {
            mkdir(ROOT_DIR . "/locale/cache/{$this->appLocale}");
            $newlyCreated = true;
        }
        return $newlyCreated;
    }

    /**
     * @throws LocalizationException
     * @return array
     */
    private function buildGlobalArrayFromJsonFiles()
    {
        $globalArray = [];
        foreach (recursiveGlob(ROOT_DIR . "/locale/{$this->appLocale}/*.json") as $file) {
            $string = file_get_contents($file);
            $jsonAssociativeArray = json_decode($string, true);
            $jsonLastError = json_last_error();
            if ($jsonLastError > JSON_ERROR_NONE) {
                throw new LocalizationException($jsonLastError, $file);
            }
            $globalArray = array_merge($globalArray, $jsonAssociativeArray);
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

    private function initializeLocale()
    {
        $charset = Configuration::getApplicationConfiguration('charset');
        $locale = $this->appLocale . '.' . $charset;
        setlocale(LC_MESSAGES, $locale);
        setlocale(LC_TIME, $locale);
        setlocale(LC_CTYPE, $locale);
        putenv("LANG=" . $this->appLocale);
    }

    private function __construct()
    {
        $this->appLocale = Session::getInstance()->read(self::SESSION_LANGUAGE_KEY,
            Configuration::getApplicationConfiguration('locale'));
        $this->initializeLocale();
    }
}
