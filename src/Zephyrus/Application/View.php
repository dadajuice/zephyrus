<?php namespace Zephyrus\Application;

use Pug\Pug;
use Zephyrus\Security\ContentSecurityPolicy;
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
    private $content;

    /**
     * @var Pager
     */
    private $pager = null;

    /**
     * @param Pug $pug
     * @param string $content can be direct Pug input or path
     */
    public function __construct(Pug $pug, string $content)
    {
        $this->pug = $pug;
        $this->addPagerKeyword();
        $this->content = $content;
    }

    /**
     * @param Pager $pager
     */
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
        $output = $this->pug->render($this->content, $args);
        Form::removeMemorizedValue();
        return $output;
    }

    private function addPagerKeyword()
    {
        $this->pug->setKeyword('pager', function () {
            $begin = (is_null($this->pager))
                ? 'echo "<div><strong style=\"color: red;\">PAGER NOT DEFINED !</strong></div>"'
                : 'echo $pager';
            return [
                'beginPhp' => $begin,
                'endPhp' => ';',
            ];
        });
    }
}