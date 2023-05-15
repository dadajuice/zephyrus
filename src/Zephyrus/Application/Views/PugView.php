<?php namespace Zephyrus\Application\Views;

use JsPhpize\JsPhpizePhug;
use Phug\Phug;
use RuntimeException;
use Zephyrus\Application\Configuration;
use Zephyrus\Application\Feedback;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Form;
use Zephyrus\Network\Response;

class PugView extends View
{
    public const DEFAULT_CONFIGURATIONS = [
        'cache_enabled' => false, // Enable the cache feature
        'cache_directory' => "/var/cache/pug", // Cache directory for generated files
        'js_syntax' => true, // Enable JsPhpizePhug extension
        'debug_enabled' => true, // Enable Pug debugging
    ];

    /**
     * Loaded configurations for the Pug engine.
     *
     * @var array
     */
    private array $configurations;

    public static function renderFromString(string $pugCode, array $args = []): string
    {
        return Phug::render($pugCode, $args);
    }

    public static function generateCache(): array
    {
        return Phug::cacheDirectory(realpath(ROOT_DIR . '/app/Views/'));
    }

    public static function addFunction($name, $action)
    {
        Phug::share([$name => $action]);
    }

    public function __construct(string $pageToRender, array $configurations = [])
    {
        parent::__construct($pageToRender);
        $this->initializeConfigurations($configurations);
        $this->initializeCache();
        $this->initializeDebug();
        $this->initializeJsExtension();
        Phug::setOption('paths', [realpath(ROOT_DIR . '/public')]);
        Phug::share([
            'flash' => Flash::readAll(),
            'feedback' => Feedback::readAll()
        ]);
    }

    public function render(array $args = []): Response
    {
        if ($this->isAvailable()) {
            $output = Phug::renderFile($this->getPath(), $args);
            Form::removeMemorizedValue();
            Flash::clearAll();
            Feedback::clearAll();
            return $this->buildResponse($output);
        }

        throw new RuntimeException("The specified view file [{$this->getPage()}] is not available (not readable or does not exists)");
    }

    protected function buildPathFromPage(string $pageToRender): string
    {
        return realpath(ROOT_DIR . '/app/Views/' . $pageToRender . '.pug');
    }

    private function initializeConfigurations(array $configurations)
    {
        if (empty($configurations)) {
            $configurations = Configuration::getConfiguration('pug') ?? self::DEFAULT_CONFIGURATIONS;
        }
        $this->configurations = $configurations;
    }

    private function initializeCache()
    {
        $cacheEnabled = (isset($this->configurations['cache_enabled']))
            ? (bool) $this->configurations['cache_enabled']
            : self::DEFAULT_CONFIGURATIONS['cache_enabled'];
        $cacheDirectory = (isset($this->configurations['cache_directory']))
            ? (bool) $this->configurations['cache_directory']
            : self::DEFAULT_CONFIGURATIONS['cache_directory'];
        Phug::setOption('cache_dir', $cacheEnabled ? $cacheDirectory : false);
    }

    private function initializeDebug()
    {
        Phug::addExtension(JsPhpizePhug::class);
        $debugEnabled = (isset($this->configurations['debug_enabled']))
            ? (bool) $this->configurations['debug_enabled']
            : self::DEFAULT_CONFIGURATIONS['debug_enabled'];
        Phug::setOption('debug', $debugEnabled);
    }

    private function initializeJsExtension()
    {
        $jsEnabled = (isset($this->configurations['js_syntax']))
            ? (bool) $this->configurations['js_syntax']
            : self::DEFAULT_CONFIGURATIONS['js_syntax'];
        if ($jsEnabled) {
            Phug::addExtension(JsPhpizePhug::class);
        }
    }
}
