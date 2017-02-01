<?php

namespace Zephyrus\Application;

use DirectoryIterator;

class ClassLocator
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $namespace;

    /**
     * Returns all defined namespaces under PSR-4 section of the composer.json
     * file.
     *
     * @return array
     */
    public static function getDefinedNamespaces(): array
    {
        $composerJsonPath = ROOT_DIR . DIRECTORY_SEPARATOR . 'composer.json';
        $composerConfig = json_decode(file_get_contents($composerJsonPath));
        $psr4 = 'psr-4';

        return (array) $composerConfig->autoload->$psr4;
    }

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
        $this->directory = $this->getNamespaceDirectory();
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Returns all class names (including complete namespace) for a given
     * composer registered PSR-4 namespace.
     *
     * @return array
     */
    public function getClasses(): array
    {
        $files = [];
        foreach (new DirectoryIterator($this->directory) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }
            $files[] = $fileInfo->getFilename();
        }
        $classes = array_map(function ($file) {
            $namespace = rtrim($this->namespace, '\\');

            return $namespace . '\\' . str_replace('.php', '', $file);
        }, $files);

        return $classes;
    }

    /**
     * Returns the complete real path for a given namespace based on the given
     * definitions in the composer.json file.
     *
     * @throws \Exception
     *
     * @return string
     */
    private function getNamespaceDirectory(): string
    {
        $composerNamespaces = self::getDefinedNamespaces();
        $namespaceFragments = explode('\\', $this->namespace);
        $undefinedFragments = [];
        while ($namespaceFragments) {
            $possibleNamespace = implode('\\', $namespaceFragments) . '\\';
            if (array_key_exists($possibleNamespace, $composerNamespaces)) {
                return realpath(ROOT_DIR . DIRECTORY_SEPARATOR . $composerNamespaces[$possibleNamespace] .
                    implode(DIRECTORY_SEPARATOR, $undefinedFragments));
            }
            $undefinedFragments[] = array_pop($namespaceFragments);
        }
        throw new \Exception("Specified namespace [$this->namespace] has not been defined");
    }
}
