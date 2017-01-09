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
        $this->addPagerKeyword();
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
        $this->keywords['csrf'] = function() {
            return [
                'beginPhp' => 'echo Zephyrus\Security\CsrfGuard::getInstance()->generateHiddenFields()',
                'endPhp' => ';',
            ];
        };
    }

    private function addPagerKeyword()
    {
        $this->keywords['pager'] = function () {
            //if (is_null($this->pager)) {
            $begin = 'echo "<div><strong style=\"color: red;\">PAGER NOT DEFINED !</strong></div>"';
            //} else {
            //    $begin = 'echo $pager';
            //}
            return [
                'beginPhp' => $begin,
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
        $this->functions['format'] = function ($type, $value, ...$args) {
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
}