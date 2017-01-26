<?php namespace Zephyrus\Application;

use Pug\Pug;
use Zephyrus\Security\ContentSecurityPolicy;

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
        if (!file_exists($path) || !is_readable($path)) {
            throw new \Exception("The specified view file [$path] is not available (not readable or does not exists)");
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
        $this->initializeDefaultFunctions();
        $this->addCsrfKeyword();
        $this->pug->share(Flash::readAll());
    }

    private function __construct()
    {
        $this->buildPug();
        $this->addPugHelpers();
    }

    private function initializeDefaultFunctions()
    {
        $functions = [];
        $functions['nonce'] = $this->addNonceFunction();
        $functions['format'] = $this->addFormatFunction();
        $functions['val'] = $this->addValFunction();
        $functions['sess'] = $this->addSessionFunction();
        $this->pug->share($functions);
    }

    private function addNonceFunction()
    {
        return function () {
            return ContentSecurityPolicy::getRequestNonce();
        };
    }

    private function addValFunction()
    {
        return function ($fieldId, $defaultValue = "") {
            return Form::readMemorizedValue($fieldId, $defaultValue);
        };
    }

    private function addSessionFunction()
    {
        return function ($key) {
            return Session::getInstance()->read($key);
        };
    }

    private function addFormatFunction()
    {
        return function ($type, ...$args) {
            $class = '\Zephyrus\Application\Formatter';
            switch ($type) {
                case 'filesize':
                    return forward_static_call_array([$class, 'formatHumanFileSize'], $args);
                case 'time':
                    return forward_static_call_array([$class, 'formatTime'], $args);
                case 'elapsed':
                    return forward_static_call_array([$class, 'formatElapsedDateTime'], $args);
                case 'datetime':
                    return forward_static_call_array([$class, 'formatDateTime'], $args);
                case 'date':
                    return forward_static_call_array([$class, 'formatDate'], $args);
                case 'percent':
                    return forward_static_call_array([$class, 'formatPercent'], $args);
                case 'money':
                    return forward_static_call_array([$class, 'formatMoney'], $args);
                case 'decimal':
                    return forward_static_call_array([$class, 'formatDecimal'], $args);
            }
            return 'FORMAT TYPE [' . $type . '] NOT DEFINED !';
        };
    }

    private function addCsrfKeyword()
    {
        $this->pug->addKeyword('csrf', function () {
            return [
                'beginPhp' => 'echo Zephyrus\Security\CsrfGuard::getInstance()->generateHiddenFields()',
                'endPhp' => ';',
            ];
        });
    }
}
