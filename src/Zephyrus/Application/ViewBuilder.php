<?php namespace Zephyrus\Application;

use Pug\Pug;

class ViewBuilder
{
    /**
     * @var ViewBuilder
     */
    private static $instance = null;

    /**
     * @var Pug
     */
    private $pug;

    /**
     * @return ViewBuilder
     */
    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function build(string $pageToRender): View
    {
        $path = ROOT_DIR . '/app/Views/' . $pageToRender . '.pug';
        if (!file_exists($path) || !is_readable($path)) {
            throw new \Exception("The specified view file [$path] is not available (not readable or does not exists)");
        }
        return new View($this->pug, $path);
    }

    public function buildFromString(string $pugCode): View
    {
        return new View($this->pug, $pugCode);
    }

    public function addFunction($name, $action)
    {
        $this->pug->share([$name => $action]);
    }

    private function buildPug()
    {
        $cacheEnabled = Configuration::getConfiguration('pug', 'cache_enabled');
        $options = [
            'basedir' => ROOT_DIR . '/public',
            'expressionLanguage' => 'js',
            'upToDateCheck' => true,
            'cache' => $cacheEnabled
                ? Configuration::getConfiguration('pug', 'cache_directory')
                : null
        ];
        $this->pug = new Pug($options);
    }

    private function __construct()
    {
        $this->buildPug();
        $this->pug->share(Flash::readAll());
        $this->pug->share(Feedback::readAll());
    }
}
