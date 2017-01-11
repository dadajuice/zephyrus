<?php namespace Zephyrus\Application;

use Zephyrus\Security\ContentSecurityPolicy;

class ViewHelper
{
    private $keywords = [];
    private $functions = [];

    public function __construct()
    {
        $this->addNonceFunction();
        $this->addEmailFunction();
        $this->addFormatFunction();
        $this->addValFunction();
        $this->addCsrfKeyword();
    }

    public function addFunction($name, callable $callback)
    {
        $this->functions[$name] = $callback;
    }

    public function addKeyword($name, callable $callback)
    {
        $this->keywords[$name] = $callback;
    }

    public function getKeywords()
    {
        return $this->keywords;
    }

    public function getFunctions()
    {
        return $this->functions;
    }

    private function addCsrfKeyword()
    {
        $this->keywords['csrf'] = function () {
            return [
                'beginPhp' => 'echo Zephyrus\Security\CsrfGuard::getInstance()->generateHiddenFields()',
                'endPhp' => ';',
            ];
        };
    }

    private function addEmailFunction()
    {
        $this->functions['email'] = function ($email) {
            echo secureEmail($email);
        };
    }

    private function addNonceFunction()
    {
        $this->functions['nonce'] = function () {
            return ContentSecurityPolicy::getRequestNonce();
        };
    }

    private function addValFunction()
    {
        $this->functions['val'] = function ($fieldId, $defaultValue = "") {
            return Form::readMemorizedValue($fieldId, $defaultValue);
        };
    }

    private function addFormatFunction()
    {
        $this->functions['format'] = function ($type, ...$args) {
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
}
