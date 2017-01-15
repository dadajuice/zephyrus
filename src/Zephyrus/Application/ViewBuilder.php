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
     * @var ViewHelper
     */
    private $viewHelper;

    /**
     * @return ViewBuilder
     */
    public static function getInstance(): ViewBuilder
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function build(string $pageToRender): View
    {
        $path = ROOT_DIR . '/app/views/' . $pageToRender . $this->pug->getExtension();
        if (!file_exists($path)) {
            throw new \Exception("The specified view file [$path] does not exists");
        }
        if (!is_readable($path)) {
            throw new \Exception("The specified view file [$path] is not readable");
        }
        return new View($this->pug, $path);
    }

    public function buildFromString(string $pugCode): View
    {
        return new View($this->pug, $pugCode);
    }

    public function addKeyword($keyword, $action)
    {
        $this->pug->addKeyword($keyword, $action);
    }

    public function addFunction($name, $action)
    {
        $this->pug->share([$name => $action]);
    }

    private function buildPug()
    {
        $options = [
            'basedir' => ROOT_DIR . '/public',
            'expressionLanguage' => 'js',
            'upToDateCheck' => true
        ];
        if (Configuration::getApplicationConfiguration('env') == "prod") {
            $options['upToDateCheck'] = false;
            $options['cache'] = Configuration::getConfiguration('pug', 'cache');
        }
        $this->pug = new Pug($options);
    }

    private function addPugHelpers()
    {
        foreach ($this->viewHelper->getKeywords() as $keyword => $action) {
            $this->pug->addKeyword($keyword, $action);
        }
        $this->pug->share($this->viewHelper->getFunctions());
        $this->pug->share(Flash::readAll());
    }

    private function __construct()
    {
        $this->buildPug();
        $this->viewHelper = new ViewHelper();
        $this->addPugHelpers();
    }
}
