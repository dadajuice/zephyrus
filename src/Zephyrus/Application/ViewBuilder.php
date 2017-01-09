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
        $this->assignPugSharedArguments();
        $this->addPagerKeyword();
        $this->addCsrfKeyword();
    }

    private function assignPugSharedArguments()
    {
        $args = Flash::readAll();
        $args['val'] = function ($fieldId, $defaultValue = "") {
            return Form::readMemorizedValue($fieldId, $defaultValue);
        };
        $args['format'] = $this->addFormatFunction();
        $args['email'] = $this->addEmailFunction();
        $args['nonce'] = $this->addNonceFunction();
        $this->pug->share($args);
    }

    private function addFormatFunction()
    {
        return function ($type, $value, ...$args) {
            $argc = count($args);
            switch ($type) {
                case 'filesize':
                    return Formatter::formatHumanFileSize($value);
                case 'time':
                    return Formatter::formatTime($value);
                case 'elapsed':
                    return Formatter::formatElapsedDateTime($value);
                case 'datetime':
                    return Formatter::formatDateTime($value);
                case 'date':
                    return Formatter::formatDate($value);
                case 'percent':
                    if ($argc == 0) {
                        return Formatter::formatPercent($value);
                    } elseif ($argc == 1) {
                        return Formatter::formatPercent($value, $args[0]);
                    } elseif ($argc == 2) {
                        return Formatter::formatPercent($value, $args[0], $args[1]);
                    } else {
                        return 'Percent format must between 0 and 2 arguments (' . $argc . ' provided)';
                    }
                case 'money':
                    if ($argc == 0) {
                        return Formatter::formatMoney($value);
                    } elseif ($argc == 1) {
                        return Formatter::formatMoney($value, $args[0]);
                    } elseif ($argc == 2) {
                        return Formatter::formatMoney($value, $args[0], $args[1]);
                    } else {
                        return 'Money format must have between 0 and 3 arguments (' . $argc . ' provided)';
                    }
                case 'decimal':
                    //$arguments[] = $value;
                    //return forward_static_call_array(['Formatter', 'formatDecimal'], array_merge($arguments, $args));
                    if ($argc == 0) {
                        return Formatter::formatDecimal($value);
                    } elseif ($argc == 1) {
                        return Formatter::formatDecimal($value, $args[0]);
                    } elseif ($argc == 2) {
                        return Formatter::formatDecimal($value, $args[0], $args[1]);
                    } else {
                        return 'Decimal format must have between 0 and 3 arguments (' . $argc . ' provided)';
                    }
            }
            return 'FORMAT TYPE [' . $type . '] NOT DEFINED !';
        };
    }

    private function addEmailFunction()
    {
        return function ($email) {
            echo secureEmail($email);
        };
    }

    private function addNonceFunction()
    {
        return function () {
            return ContentSecurityPolicy::getRequestNonce();
        };
    }

    private function addPagerKeyword()
    {
        $this->pug->addKeyword('pager', function () {
            if (is_null($this->pager)) {
                $begin = 'echo "<div><strong style=\"color: red;\">PAGER NOT DEFINED !</strong></div>"';
            } else {
                $begin = 'echo $pager';
            }
            return array(
                'beginPhp' => $begin,
                'endPhp' => ';',
            );
        });
    }

    private function addCsrfKeyword()
    {
        $this->pug->addKeyword('csrf', function () {
            return array(
                'beginPhp' => 'echo Zephyrus\Security\CsrfGuard::getInstance()->generateHiddenFields()',
                'endPhp' => ';',
            );
        });
    }

    private function __construct()
    {
        $this->buildPug();
    }
}
