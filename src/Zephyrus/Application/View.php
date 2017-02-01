<?php

namespace Zephyrus\Application;

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
    private $content;

    /**
     * @param Pug    $pug
     * @param string $content can be direct Pug input or path
     */
    public function __construct(Pug $pug, string $content)
    {
        $this->pug = $pug;
        $this->content = $content;
    }

    /**
     * @param array $args
     *
     * @return string
     */
    public function render($args = [])
    {
        $output = $this->pug->render($this->content, $args);
        Form::removeMemorizedValue();

        return $output;
    }
}
