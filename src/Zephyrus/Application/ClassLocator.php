<?php namespace Zephyrus\Application;

class ClassLocator
{
    /**
     * Returns all class names (including complete namespace) for a given
     * composer registered PSR-4 namespace.
     *
     * @param $namespace
     * @return array
     */
    public static function getClassesInNamespace($namespace)
    {
        $files = scandir(self::getNamespaceDirectory($namespace));
        $classes = array_map(function ($file) use ($namespace) {
            return $namespace . '\\' . str_replace('.php', '', $file);
        }, $files);
        return array_values(array_filter($classes, function ($possibleClass) {
            return class_exists($possibleClass);
        }));
    }

    /**
     * Returns all defined namespaces under PSR-4 section of the composer.json
     * file.
     *
     * @return array
     */
    public static function getDefinedNamespaces()
    {
        $composerJsonPath = ROOT_DIR . DIRECTORY_SEPARATOR . 'composer.json';
        $composerConfig = json_decode(file_get_contents($composerJsonPath));
        $psr4 = "psr-4";
        return (array) $composerConfig->autoload->$psr4;
    }

    /**
     * Returns the complete real path for a given namespace based on the given
     * definitions in the composer.json file.
     *
     * @param $namespace
     * @return string
     * @throws \Exception
     */
    private static function getNamespaceDirectory($namespace)
    {
        $composerNamespaces = self::getDefinedNamespaces();
        $namespaceFragments = explode('\\', $namespace);
        $undefinedFragments = [];
        while ($namespaceFragments) {
            $possibleNamespace = implode('\\', $namespaceFragments) . '\\';
            if (array_key_exists($possibleNamespace, $composerNamespaces)) {
                return realpath(ROOT_DIR . DIRECTORY_SEPARATOR . $composerNamespaces[$possibleNamespace] .
                    implode('/', $undefinedFragments));
            }
            $undefinedFragments[] = array_pop($namespaceFragments);
        }
        throw new \Exception("Specified namespace [$namespace] has not been defined");
    }
}
