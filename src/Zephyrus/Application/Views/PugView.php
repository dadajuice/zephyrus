<?php namespace Zephyrus\Application\Views;

use Phug\PhugException;
use RuntimeException;
use Zephyrus\Application\Feedback;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Form;
use Zephyrus\Network\Response;

class PugView extends View
{
    private PugEngine $engine;

    public function __construct(string $pageToRender, ?PugEngine $engine = null)
    {
        parent::__construct($pageToRender);
        $this->engine = $engine ?? new PugEngine();
    }

    public function render(array $args = []): Response
    {
        if ($this->isAvailable()) {
            ob_start();
            $this->engine->renderFromFile($this->getPath(), $args);
            $output = ob_get_clean();
            Form::removeMemorizedValue();
            Flash::clearAll();
            Feedback::clear();
            return $this->buildResponse($output);
        }

        throw new RuntimeException("The specified view file [{$this->getPage()}] is not available (not readable or does not exists)");
    }

    protected function buildPathFromPage(string $pageToRender): string
    {
        return realpath(ROOT_DIR . '/app/Views/' . $pageToRender . '.pug');
    }
}
