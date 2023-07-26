<?php namespace Zephyrus\Application\Views;

use JsPhpize\JsPhpizePhug;
use Phug\Phug;
use Phug\PhugException;
use Phug\RendererException;
use Zephyrus\Application\Configuration;
use Zephyrus\Application\Flash;

class PugEngine
{
    public const DEFAULT_CONFIGURATIONS = [
        'cache_enabled' => true, // Enable the cache feature
        'cache_directory' => ROOT_DIR . "/cache/pug", // Cache directory for generated files
        'js_syntax' => true, // Enable JsPhpizePhug extension
        'debug_enabled' => true, // Enable Pug debugging
    ];

    /**
     * Loaded configurations for the Pug engine.
     *
     * @var array
     */
    private array $configurations;

    /**
     * @throws PhugException
     */
    public function __construct(array $configurations = [])
    {
        $this->initializeConfigurations($configurations);
        $this->initializeCache();
        $this->initializeDebug();
        $this->initializeJsExtension();
        Phug::setOption('paths', [realpath(ROOT_DIR . '/public')]);
        Phug::share([
            'flash' => Flash::readAll()
        ]);
    }

    public function renderFromString(string $pugCode, array $args = []): string
    {
        return Phug::render($pugCode, $args);
    }

    public function renderFromFile(string $path, array $args = []): string
    {
        return Phug::renderFile($path, $args);
    }

    /**
     * @throws RendererException
     */
    public function generateCache(): array
    {
        return Phug::cacheDirectory(realpath(ROOT_DIR . '/app/Views/'));
    }

    public function addFunction($name, $action): void
    {
        Phug::share([$name => $action]);
    }

    private function initializeConfigurations(array $configurations): void
    {
        if (empty($configurations)) {
            $configurations = Configuration::getConfiguration('pug') ?? self::DEFAULT_CONFIGURATIONS;
        }
        $this->configurations = $configurations;
    }

    private function initializeCache(): void
    {
        $cacheEnabled = (isset($this->configurations['cache_enabled']))
            ? (bool) $this->configurations['cache_enabled']
            : self::DEFAULT_CONFIGURATIONS['cache_enabled'];
        $cacheDirectory = (isset($this->configurations['cache_directory']))
            ? (bool) $this->configurations['cache_directory']
            : self::DEFAULT_CONFIGURATIONS['cache_directory'];
        Phug::setOption('cache_dir', $cacheEnabled ? $cacheDirectory : false);
    }

    /**
     * @throws PhugException
     */
    private function initializeDebug(): void
    {
        $debugEnabled = (isset($this->configurations['debug_enabled']))
            ? (bool) $this->configurations['debug_enabled']
            : self::DEFAULT_CONFIGURATIONS['debug_enabled'];
        Phug::setOption('debug', $debugEnabled);
    }

    /**
     * @throws PhugException
     */
    private function initializeJsExtension(): void
    {
        $jsEnabled = (isset($this->configurations['js_syntax']))
            ? (bool) $this->configurations['js_syntax']
            : self::DEFAULT_CONFIGURATIONS['js_syntax'];
        if ($jsEnabled) {
            Phug::addExtension(JsPhpizePhug::class);
        }
    }
}
