<?php namespace Zephyrus\Application;

use Pug\Pug;
use Zephyrus\Utilities\Pager;

class View
{
    /**
     * @var Pug
     */
    private $pug;

    /**
     * @var string
     */
    private $pageToRender;

    /**
     * @var Pager
     */
    private $pager = null;

    /**
     * @param string $pageToRender
     */
    public function __construct($pageToRender)
    {
        $this->pageToRender = $pageToRender;
        $this->buildPug();
    }

    public function setPager(Pager $pager)
    {
        $this->pager = $pager;
    }

    /**
     * @param array $args
     * @return string
     */
    public function render($args = [])
    {
        if (isset($args['pager'])) {
            throw new \RuntimeException("pager argument already set ! pager variable is reserved.");
        }
        if (!is_null($this->pager)) {
            $args['pager'] = $this->pager;
        }
        $output = $this->pug->render(ROOT_DIR . '/app/views/' . $this->pageToRender . $this->pug->getExtension(), $args);
        clearFieldMemory();
        return $output;
    }

    private function buildPug()
    {
        $options = [
            'cache' => Configuration::getConfiguration('pug', 'cache'),
            'basedir' => ROOT_DIR . '/public'
        ];
        if (Configuration::getApplicationConfiguration('env') == "prod") {
            $options['upToDateCheck'] = false;
        }
        $this->pug = new Pug($options);
        $this->assignPugSharedArguments();
        $this->addPagerKeyword();
    }

    private function assignPugSharedArguments()
    {
        $args = Flash::readAll();
        $args['_val'] = function($fieldId, $defaultValue = "") {
            return _val($fieldId, $defaultValue);
        };
        $args['format'] = $this->addFormatFunction();
        $this->pug->share($args);
    }

    private function addFormatFunction()
    {
        return function($type, $value, ...$args) {
            $argc = count($args);
            switch ($type) {
                case 'filesize' :
                    return Formatter::formatHumanFileSize($value);
                case 'time' :
                    return Formatter::formatTime(($value instanceof \DateTime) ? $value : new \DateTime($value));
                case 'elapsed':
                case 'french_elapsed' :
                    return Formatter::formatElapsedDateTime(($value instanceof \DateTime) ? $value : new \DateTime($value));
                case 'datetime':
                case 'french_datetime' :
                    if ($argc == 0) {
                        return Formatter::formatFrenchDateTime(($value instanceof \DateTime) ? $value : new \DateTime($value));
                    } elseif ($argc == 1) {
                        return Formatter::formatFrenchDateTime(($value instanceof \DateTime) ? $value : new \DateTime($value), $args[0]);
                    } elseif ($argc == 2) {
                        return Formatter::formatFrenchDateTime(($value instanceof \DateTime) ? $value : new \DateTime($value), $args[0], $args[1]);
                    } else {
                        return 'French datetime format must between 0 and 2 arguments (' . $argc . ' provided)';
                    }
                case 'date':
                case 'french_date' :
                    if ($argc == 0) {
                        return Formatter::formatFrenchDate(($value instanceof \DateTime) ? $value : new \DateTime($value));
                    } elseif ($argc == 1) {
                        return Formatter::formatFrenchDate(($value instanceof \DateTime) ? $value : new \DateTime($value), $args[0]);
                    } elseif ($argc == 2) {
                        return Formatter::formatFrenchDate(($value instanceof \DateTime) ? $value : new \DateTime($value), $args[0], $args[1]);
                    } else {
                        return 'French date format must between 0 and 2 arguments (' . $argc . ' provided)';
                    }
                case 'period':
                case 'french_period' :
                    if ($argc == 2) {
                        return Formatter::formatFrenchPeriod(
                            ($value instanceof \DateTime) ? $value : new \DateTime($value),
                            ($args[0] instanceof \DateTime) ? $args[0] : new \DateTime($args[0]));
                    } else {
                        return 'French period format must have 2 arguments (' . $argc . ' provided)';
                    }
                case 'percent' :
                    if ($argc == 0) {
                        return Formatter::formatPercent($value);
                    } elseif ($argc == 1) {
                        return Formatter::formatPercent($value, $args[0]);
                    } elseif ($argc == 2) {
                        return Formatter::formatPercent($value, $args[0], $args[1]);
                    } else {
                        return 'Percent format must between 0 and 2 arguments (' . $argc . ' provided)';
                    }
                case 'money' :
                    if ($argc == 0) {
                        return Formatter::formatMoney($value);
                    } elseif ($argc == 1) {
                        return Formatter::formatMoney($value, $args[0]);
                    } elseif ($argc == 2) {
                        return Formatter::formatMoney($value, $args[0], $args[1]);
                    }  elseif ($argc == 3) {
                        return Formatter::formatMoney($value, $args[0], $args[1], $args[2]);
                    } else {
                        return 'Money format must have between 0 and 3 arguments (' . $argc . ' provided)';
                    }
                case 'decimal' :
                    if ($argc == 0) {
                        return Formatter::formatDecimal($value);
                    } elseif ($argc == 1) {
                        return Formatter::formatDecimal($value, $args[0]);
                    } elseif ($argc == 2) {
                        return Formatter::formatDecimal($value, $args[0], $args[1]);
                    }  elseif ($argc == 3) {
                        return Formatter::formatDecimal($value, $args[0], $args[1], $args[2]);
                    } else {
                        return 'Decimal format must have between 0 and 3 arguments (' . $argc . ' provided)';
                    }
            }
            return 'FORMAT TYPE [' . $type . '] NOT DEFINED !';
        };
    }

    private function addPagerKeyword()
    {
        $this->pug->addKeyword('pager', function ($arguments, $block, $keyword) {
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
}