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
        $output = $this->pug->render(ROOT_DIR . '/app/views/' . $this->pageToRender . $this->pug->getExtension(), $args);
        clearFieldMemory();
        return $output;
    }

    private function buildPug()
    {
        $options = [
            'cache' => Configuration::getConfiguration('pug', 'cache'),
            'basedir' => ROOT_DIR . '/app/views'
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
        $this->pug->share($args);
    }

    private function addPagerKeyword()
    {
        $this->pug->addKeyword('pager', function ($arguments, $block, $keyword) {
            if (is_null($this->pager)) {
                $begin = '<div><strong style="color: red;">PAGER NOT DEFINED !</strong></div>';
            } else {

                $begin = $this->pager->__toString();    
            }

            return array(
                'begin' => $begin,
                'end' => '',
            );
        });
    }
}