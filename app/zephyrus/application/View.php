<?php namespace Zephyrus\Application;

use Pug\Pug;

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

    public function __construct($pageToRender)
    {
        $this->pageToRender = $pageToRender;
        $this->buildPug();
    }

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
        //$this->addPagerKeyword();
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
        $this->pug->addKeyword('pager', new class() {
            public function __invoke($arguments, $block, $keyWord)
            {
                $badges = array();
                foreach ($block->nodes as $index => $tag) {
                    if ($tag->name === 'badge') {
                        $href = $tag->getAttribute('color');
                        $badges[] = $href['value'];
                        unset($block->nodes[$index]);
                    }
                }

                $begin = '<div class="pager">';


                return array(
                    'begin' => $begin,
                    'end' => '</div>',
                );
            }
        });
    }
}