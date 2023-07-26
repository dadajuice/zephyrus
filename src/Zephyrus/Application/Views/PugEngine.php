<?php namespace Zephyrus\Application\Views;

use JsPhpize\JsPhpizePhug;
use Phug\Phug;
use Phug\Optimizer;
use Phug\PhugException;
use Zephyrus\Application\Configuration;

class PugEngine
{
    public const DEFAULT_CONFIGURATIONS = [
        'cache_enabled' => true, // Enable the cache feature
        'cache_directory' => ROOT_DIR . "/cache/pug", // Cache directory for generated files
        'cache_update' => 'always', // always|never (useful in production if cache is done manually)
        'js_syntax' => true, // Enable JsPhpizePhug extension
        'debug_enabled' => true, // Enable Pug debugging
        'optimizer_enabled' => true // Enable Pug Optimizer
    ];

    /**
     * Loaded configurations for the Pug engine.
     *
     * @var array
     */
    private array $configurations;

    /**
     * Determine if the rendering should use the optimized call.
     *
     * @var bool
     */
    private bool $optimizerEnabled = false;

    /**
     * Keeps the internal Phug instance options.
     *
     * @var array
     */
    private array $options = [];

    /**
     * @throws PhugException
     */
    public function __construct(array $configurations = [])
    {
        $this->initializeConfigurations($configurations);
        $this->initializeCache();
        $this->initializeDebug();
        $this->initializeOptimizer();
        $this->initializeJsExtension();
        $this->initializeDefaultSharedVariables();
    }

    /**
     * Prepares a PugView instance from this engine (will include the shared variables and other settings).
     *
     * @param string $page
     * @return PugView
     */
    public function buildView(string $page): PugView
    {
        return new PugView($page, $this);
    }

    public function renderFromString(string $pugCode, array $args = []): string
    {
        return Phug::render($pugCode, $args, $this->options);
    }

    public function renderFromFile(string $path, array $args = []): void
    {
        if ($this->optimizerEnabled) {
            Optimizer::call('displayFile', [$path, $args], $this->options);
            return;
        }
        Phug::displayFile($path, $args, $this->options);
    }

    /**
     * Includes a variable or callback to Pug files rendered with this Pug Engine instance.
     *
     * @param string $name
     * @param mixed $action
     * @return void
     */
    public function share(string $name, mixed $action): void
    {
        $this->options['shared_variables'][$name] = $action;
    }

    /**
     * Add a filter which can then be used in every Pug files. E.g :add(value=4) 5. The callback must have 2 arguments:
     * the first one is the text and the second one the options given. In the above example, the text would be 5 and the
     * options would be an associative array with value=4.
     *
     * @param string $name
     * @param callable $callback
     * @throws PhugException
     * @return void
     */
    public function addFilter(string $name, callable $callback): void
    {
        Phug::setFilter($name, $callback);
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
        $cacheUpdate = (isset($this->configurations['cache_update']))
            ? (bool) $this->configurations['cache_update']
            : self::DEFAULT_CONFIGURATIONS['cache_update'];
        $this->options['cache_dir'] = $cacheEnabled ? $cacheDirectory : false;
        $this->options['up_to_date_check'] = ($cacheUpdate == "always");
    }

    private function initializeDebug(): void
    {
        $debugEnabled = (isset($this->configurations['debug_enabled']))
            ? (bool) $this->configurations['debug_enabled']
            : self::DEFAULT_CONFIGURATIONS['debug_enabled'];
        $this->options['debug'] = $debugEnabled;
    }

    private function initializeOptimizer(): void
    {
        $this->optimizerEnabled = (isset($this->configurations['optimizer_enabled']))
            ? (bool) $this->configurations['optimizer_enabled']
            : self::DEFAULT_CONFIGURATIONS['optimizer_enabled'];
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

    private function initializeDefaultSharedVariables(): void
    {
        $this->options['shared_variables'] = [];
    }
}
